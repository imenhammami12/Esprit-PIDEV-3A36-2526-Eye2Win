<?php

namespace App\Service;

use App\Entity\MatchValorant;
use App\Entity\User;
use App\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

class ClipGeneratorService
{
    private string $projectDir;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        KernelInterface $kernel,
    ) {
        $this->projectDir = $kernel->getProjectDir();
    }

    /**
     * @return array{clips:Video[],highlight:?Video,errors:string[]}
     */
    public function importFromMatch(User $user, MatchValorant $match, ?Video $sourceVideo = null, string $visibility = Video::VISIBILITY_PRIVATE): array
    {
        $errors = [];
        $clips = [];

        try {
            $clips = $this->generateClipsFromMatch($user, $match, $sourceVideo, $visibility);
        } catch (\Throwable $e) {
            $errors[] = $e->getMessage();
            $this->logger->error('Clip generation failed', ['matchId' => $match->getTrackerMatchId(), 'error' => $e->getMessage()]);
        }

        $highlight = null;
        if ($clips !== []) {
            try {
                $highlight = $this->compileHighlight($user, $match, $clips, $visibility);
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
                $this->logger->error('Highlight compilation failed', ['matchId' => $match->getTrackerMatchId(), 'error' => $e->getMessage()]);
            }
        }

        return [
            'clips' => $clips,
            'highlight' => $highlight,
            'errors' => $errors,
        ];
    }

    /**
     * @return Video[]
     */
    public function generateClipsFromMatch(User $user, MatchValorant $match, ?Video $sourceVideo = null, string $visibility = Video::VISIBILITY_PRIVATE): array
    {
        $sourcePath = $this->resolveSourcePath($sourceVideo);
        if ($sourcePath === null) {
            throw new \RuntimeException('Source video not found to generate clips.');
        }

        $killEvents = $this->extractKillEvents($match);
        if ($killEvents === []) {
            throw new \RuntimeException('No kills detected in Tracker.gg data for this match.');
        }

        $clipDirectory = $this->ensureDirectory($this->projectDir . '/public/uploads/clips');
        $thumbDirectory = $this->ensureDirectory($this->projectDir . '/public/uploads/clips/thumbs');

        $clips = [];
        foreach ($killEvents as $index => $killEvent) {
            $start = max(0, (float) ($killEvent['second'] ?? 0));
            $duration = max(2.0, min(5.0, (float) ($killEvent['duration'] ?? 3.0)));
            $label = (string) ($killEvent['label'] ?? ('Kill ' . ($index + 1)));

            $clipFileName = sprintf('clip_%s_%s_%d.mp4', $match->getTrackerMatchId(), date('YmdHis'), $index + 1);
            $thumbFileName = str_replace('.mp4', '.jpg', $clipFileName);

            $clipAbsolutePath = $clipDirectory . '/' . $clipFileName;
            $thumbAbsolutePath = $thumbDirectory . '/' . $thumbFileName;

            $this->runFfmpeg([
                'ffmpeg', '-y', '-ss', (string) $start, '-i', $sourcePath,
                '-t', (string) $duration, '-c:v', 'libx264', '-preset', 'veryfast',
                '-c:a', 'aac', '-movflags', '+faststart', $clipAbsolutePath,
            ], 'Clip extraction failed');

            $this->runFfmpeg([
                'ffmpeg', '-y', '-ss', (string) ($start + 0.3), '-i', $sourcePath,
                '-frames:v', '1', '-q:v', '2', $thumbAbsolutePath,
            ], 'Clip thumbnail generation failed');

            $clip = new Video();
            $clip->setTitle(sprintf('%s - %s', $match->getMapName() ?: 'Match', $label))
                ->setGameType('Valorant')
                ->setType(Video::TYPE_CLIP)
                ->setFilePath('/uploads/clips/' . $clipFileName)
                ->setThumbnailPath('/uploads/clips/thumbs/' . $thumbFileName)
                ->setDuration($duration)
                ->setStatus('GENERATED')
                ->setVisibility($visibility)
                ->setUploadedAt(new \DateTime())
                ->setUploadedBy($user)
                ->setMatchExternalId($match->getTrackerMatchId())
                ->setKillInfo($label)
                ->setMetadata([
                    'killSecond' => $start,
                    'sourceMatchId' => $match->getTrackerMatchId(),
                    'rawKill' => $killEvent,
                ]);

            $this->entityManager->persist($clip);
            $clips[] = $clip;
        }

        $this->entityManager->flush();

        return $clips;
    }

    /**
     * @param Video[] $clips
     */
    public function compileHighlight(User $user, MatchValorant $match, array $clips, string $visibility = Video::VISIBILITY_PRIVATE): Video
    {
        if ($clips === []) {
            throw new \RuntimeException('Cannot compile a highlight without clips.');
        }

        $highlightDirectory = $this->ensureDirectory($this->projectDir . '/public/uploads/highlights');
        $thumbDirectory = $this->ensureDirectory($this->projectDir . '/public/uploads/highlights/thumbs');

        $fileName = sprintf('highlight_%s_%s.mp4', $match->getTrackerMatchId(), date('YmdHis'));
        $thumbName = str_replace('.mp4', '.jpg', $fileName);
        $outputPath = $highlightDirectory . '/' . $fileName;
        $thumbPath = $thumbDirectory . '/' . $thumbName;

        $concatList = $this->createConcatFile($clips);

        try {
            $this->runFfmpeg([
                'ffmpeg', '-y', '-f', 'concat', '-safe', '0', '-i', $concatList,
                '-c:v', 'libx264', '-preset', 'veryfast', '-c:a', 'aac', '-movflags', '+faststart',
                $outputPath,
            ], 'Highlight compilation failed');
        } finally {
            @unlink($concatList);
        }

        $this->runFfmpeg([
            'ffmpeg', '-y', '-ss', '1', '-i', $outputPath, '-frames:v', '1', '-q:v', '2', $thumbPath,
        ], 'Highlight thumbnail generation failed');

        $highlight = new Video();
        $highlight->setTitle(sprintf('Highlight %s', $match->getTrackerMatchId()))
            ->setGameType('Valorant')
            ->setType(Video::TYPE_HIGHLIGHT)
            ->setFilePath('/uploads/highlights/' . $fileName)
            ->setThumbnailPath('/uploads/highlights/thumbs/' . $thumbName)
            ->setStatus('COMPILED')
            ->setVisibility($visibility)
            ->setUploadedAt(new \DateTime())
            ->setUploadedBy($user)
            ->setMatchExternalId($match->getTrackerMatchId())
            ->setMetadata([
                'clipCount' => count($clips),
                'sourceMatchId' => $match->getTrackerMatchId(),
            ]);

        foreach ($clips as $clip) {
            $highlight->addClip($clip);
        }

        $this->entityManager->persist($highlight);
        $this->entityManager->flush();

        return $highlight;
    }

    private function resolveSourcePath(?Video $sourceVideo): ?string
    {
        if (!$sourceVideo instanceof Video) {
            return null;
        }

        $path = (string) $sourceVideo->getFilePath();
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            $absolute = $this->projectDir . '/public' . $path;
            return is_file($absolute) ? $absolute : null;
        }

        return is_file($path) ? $path : null;
    }

    /**
     * @return array<int, array{second:float,duration:float,label:string}>
     */
    private function extractKillEvents(MatchValorant $match): array
    {
        $raw = $match->getRawData() ?? [];

        $kills = [];

        $rounds = $raw['metadata']['rounds'] ?? $raw['rounds'] ?? [];
        if (is_array($rounds)) {
            foreach ($rounds as $roundIndex => $roundData) {
                $events = $roundData['kills'] ?? [];
                if (!is_array($events)) {
                    continue;
                }

                foreach ($events as $eventIndex => $event) {
                    $second = (float) ($event['roundTimeSeconds'] ?? $event['timestamp'] ?? (($roundIndex * 90) + ($eventIndex * 3)));
                    $kills[] = [
                        'second' => $second,
                        'duration' => 3.0,
                        'label' => (string) ($event['label'] ?? ('Kill ' . (count($kills) + 1))),
                    ];
                }
            }
        }

        if ($kills !== []) {
            return $kills;
        }

        $maxKills = 0;
        foreach ($match->getJoueurs() as $joueur) {
            $stat = $joueur->getStatistique();
            if ($stat && $joueur->getRiotName() === $match->getOwner()?->getUsername()) {
                $maxKills = max($maxKills, $stat->getKills());
            }
            if ($stat) {
                $maxKills = max($maxKills, min(8, $stat->getKills()));
            }
        }

        $count = max(0, min($maxKills, 12));
        if ($count === 0) {
            return [];
        }

        $duration = max(1, $match->getDurationSeconds() ?? 1800);
        $interval = max(10, (int) floor($duration / ($count + 1)));

        for ($i = 1; $i <= $count; ++$i) {
            $kills[] = [
                'second' => (float) ($i * $interval),
                'duration' => 3.0,
                'label' => 'Kill ' . $i,
            ];
        }

        return $kills;
    }

    /**
     * @param Video[] $clips
     */
    private function createConcatFile(array $clips): string
    {
        $lines = [];
        foreach ($clips as $clip) {
            $path = (string) $clip->getFilePath();
            if ($path === '' || !str_starts_with($path, '/')) {
                throw new \RuntimeException('Invalid clip path for concatenation.');
            }

            $absolute = $this->projectDir . '/public' . $path;
            if (!is_file($absolute)) {
                throw new \RuntimeException('Missing clip file: ' . $path);
            }

            $escaped = str_replace("'", "'\\''", $absolute);
            $lines[] = "file '{$escaped}'";
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'concat_');
        if ($tmpFile === false) {
            throw new \RuntimeException('Unable to create temporary concat file.');
        }

        file_put_contents($tmpFile, implode(PHP_EOL, $lines));

        return $tmpFile;
    }

    private function ensureDirectory(string $path): string
    {
        if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) {
            throw new FileException('Unable to create directory: ' . $path);
        }

        return $path;
    }

    /**
     * @param string[] $command
     */
    private function runFfmpeg(array $command, string $errorMessage): void
    {
        $process = new Process($command);
        $process->setTimeout(1200);
        $process->run();

        if (!$process->isSuccessful()) {
            $output = trim($process->getErrorOutput() ?: $process->getOutput());
            throw new \RuntimeException($errorMessage . ($output !== '' ? ': ' . $output : ''));
        }
    }
}
