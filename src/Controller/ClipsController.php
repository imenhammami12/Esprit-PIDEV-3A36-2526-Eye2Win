<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Video;
use App\Repository\MatchValorantRepository;
use App\Repository\VideoRepository;
use App\Service\ClipGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/clips')]
class ClipsController extends AbstractController
{
    #[Route('/import', name: 'clips_import', methods: ['GET', 'POST'])]
    public function import(
        Request $request,
        MatchValorantRepository $matchRepository,
        VideoRepository $videoRepository,
        ClipGeneratorService $clipGeneratorService,
    ): Response {
        $user = $this->getSecuredUser();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('clips_import', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }

            $matchId = trim((string) $request->request->get('matchId', ''));
            if ($matchId === '') {
                $this->addFlash('error', 'Please select a match to generate clips.');
                return $this->redirectToRoute('clips_import');
            }

            $match = $matchRepository->findOneBy(['owner' => $user, 'trackerMatchId' => $matchId]);
            if (!$match) {
                throw $this->createNotFoundException('Match not found.');
            }

            $visibility = strtoupper((string) $request->request->get('visibility', Video::VISIBILITY_PRIVATE));
            if (!in_array($visibility, [Video::VISIBILITY_PRIVATE, Video::VISIBILITY_PUBLIC], true)) {
                $visibility = Video::VISIBILITY_PRIVATE;
            }

            $sourceVideo = $videoRepository->findUserSourceVideoForMatch($user, $matchId);
            $sourceVideoPath = trim((string) $request->request->get('sourceVideoPath', ''));
            if (!$sourceVideo && $sourceVideoPath !== '') {
                $sourceVideo = (new Video())
                    ->setFilePath($sourceVideoPath)
                    ->setUploadedBy($user)
                    ->setType(Video::TYPE_UPLOAD)
                    ->setVisibility($visibility)
                    ->setTitle('Manual source')
                    ->setStatus('SOURCE')
                    ->setUploadedAt(new \DateTime());
            }

            $result = $clipGeneratorService->importFromMatch($user, $match, $sourceVideo, $visibility);

            if ($result['clips'] !== []) {
                $this->addFlash('success', sprintf('%d clips generated for match %s.', count($result['clips']), $matchId));
            }
            if ($result['highlight'] instanceof Video) {
                $this->addFlash('success', 'Highlight compiled successfully.');
                return $this->redirectToRoute('clips_highlight_user', ['userId' => $user->getId()]);
            }
            if ($result['errors'] !== []) {
                $this->addFlash('error', implode(' | ', $result['errors']));
            }

            return $this->redirectToRoute('clips_import');
        }

        $matches = $matchRepository->searchForDashboard($user, [
            'player' => '',
            'team' => '',
            'match' => '',
            'archived' => '',
        ]);

        return $this->render('video/clips_import.html.twig', [
            'matches' => $matches,
        ]);
    }

    #[Route('/highlight/{userId}', name: 'clips_highlight_user', requirements: ['userId' => '\\d+'], methods: ['GET'])]
    public function highlightByUser(int $userId, VideoRepository $videoRepository): Response
    {
        $highlight = $videoRepository->findHighlightByUserId($userId);
        if (!$highlight) {
            throw $this->createNotFoundException('No highlight found for this user.');
        }

        $currentUser = $this->getUser();
        $isOwner = $currentUser instanceof User && $highlight->getUploadedBy()?->getId() === $currentUser->getId();

        if ($highlight->getVisibility() !== Video::VISIBILITY_PUBLIC) {
            $this->denyAccessUnlessGranted('ROLE_USER');
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

            if (!$this->isGranted('ROLE_ADMIN') && !$isOwner) {
                throw $this->createAccessDeniedException('This video is private.');
            }
        }

        return $this->render('video/clips_highlight.html.twig', [
            'highlight' => $highlight,
            'clips' => $highlight->getClips(),
            'canManageVisibility' => $isOwner || $this->isGranted('ROLE_ADMIN'),
            'clipsData' => $this->buildClipsDataSample($highlight),
        ]);
    }

    #[Route('/public', name: 'clips_public', methods: ['GET'])]
    public function publicHighlights(Request $request, VideoRepository $videoRepository): Response
    {
        $game = trim((string) $request->query->get('game', ''));
        $highlights = $videoRepository->findPublicHighlights($game !== '' ? $game : null);

        return $this->render('video/clips_public.html.twig', [
            'highlights' => $highlights,
            'selectedGame' => $game,
            'clipsData' => $this->buildPublicTemplateData($highlights),
        ]);
    }

    #[Route('/{id}/visibility', name: 'clips_toggle_visibility', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function toggleVisibility(Video $video, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $this->getSecuredUser();

        if (!$this->isCsrfTokenValid('clips_toggle_' . $video->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if (!$this->isGranted('ROLE_ADMIN') && $video->getUploadedBy()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Unauthorized action.');
        }

        $video->setVisibility(
            $video->getVisibility() === Video::VISIBILITY_PUBLIC ? Video::VISIBILITY_PRIVATE : Video::VISIBILITY_PUBLIC
        );

        foreach ($video->getClips() as $clip) {
            $clip->setVisibility($video->getVisibility());
        }

        $entityManager->flush();

        return $this->redirectToRoute('clips_highlight_user', ['userId' => $video->getUploadedBy()?->getId()]);
    }

    #[Route('/{id}/like', name: 'clips_like', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function like(Video $video, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('clips_like_' . $video->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if ($video->getVisibility() !== Video::VISIBILITY_PUBLIC) {
            throw $this->createAccessDeniedException('Cannot like a private video.');
        }

        $video->incrementLikes();
        $entityManager->flush();

        return $this->redirectToRoute('clips_public');
    }

    #[Route('/{id}/download', name: 'clips_download', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function download(Video $video, KernelInterface $kernel): Response
    {
        $user = $this->getUser();
        $isOwner = $user instanceof User && $video->getUploadedBy()?->getId() === $user->getId();

        if ($video->getVisibility() !== Video::VISIBILITY_PUBLIC) {
            $this->denyAccessUnlessGranted('ROLE_USER');
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

            if (!$this->isGranted('ROLE_ADMIN') && !$isOwner) {
                throw $this->createAccessDeniedException('Access denied.');
            }
        }

        $url = (string) $video->getFilePath();
        if ($url === '') {
            throw $this->createNotFoundException('File not found.');
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $this->redirect($url);
        }

        $absolutePath = str_starts_with($url, '/') ? $kernel->getProjectDir() . '/public' . $url : $url;

        if (!is_file($absolutePath)) {
            throw $this->createNotFoundException('Missing file.');
        }

        return new BinaryFileResponse($absolutePath);
    }

    private function getSecuredUser(): User
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('User is not authenticated.');
        }

        return $user;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildClipsDataSample(Video $highlight): array
    {
        $clips = [];
        foreach ($highlight->getClips() as $clip) {
            $clips[] = [
                'videoUrl' => $clip->getFilePath(),
                'thumbnail' => $clip->getThumbnailPath(),
                'killInfo' => $clip->getKillInfo() ?? 'Kill',
                'private' => $clip->getVisibility() !== Video::VISIBILITY_PUBLIC,
            ];
        }

        return [
            [
                'user' => $highlight->getUploadedBy()?->getUsername() ?? 'Youssef',
                'matchId' => $highlight->getMatchExternalId() ?? '12345',
                'clips' => $clips,
                'highlightVideo' => [
                    'videoUrl' => $highlight->getFilePath() ?? 'highlight_12345.mp4',
                    'thumbnail' => $highlight->getThumbnailPath() ?? 'highlight.jpg',
                    'private' => $highlight->getVisibility() !== Video::VISIBILITY_PUBLIC,
                ],
            ],
            [
                'user' => 'AutreJoueur',
                'matchId' => 12346,
                'clips' => [
                    [
                        'videoUrl' => 'clip3.mp4',
                        'thumbnail' => 'clip3.jpg',
                        'killInfo' => 'Kill 1',
                        'private' => false,
                    ],
                ],
                'highlightVideo' => [
                    'videoUrl' => 'highlight_12346.mp4',
                    'thumbnail' => 'highlight2.jpg',
                    'private' => false,
                ],
            ],
        ];
    }

    /**
     * @param Video[] $highlights
     * @return array<int, array<string, mixed>>
     */
    private function buildPublicTemplateData(array $highlights): array
    {
        $items = [];
        foreach ($highlights as $highlight) {
            $clips = [];
            foreach ($highlight->getClips() as $clip) {
                $clips[] = [
                    'videoUrl' => $clip->getFilePath(),
                    'thumbnail' => $clip->getThumbnailPath(),
                    'killInfo' => $clip->getKillInfo(),
                    'private' => $clip->getVisibility() !== Video::VISIBILITY_PUBLIC,
                ];
            }

            $items[] = [
                'user' => $highlight->getUploadedBy()?->getUsername() ?? 'Unknown',
                'matchId' => $highlight->getMatchExternalId(),
                'clips' => $clips,
                'highlightVideo' => [
                    'videoUrl' => $highlight->getFilePath(),
                    'thumbnail' => $highlight->getThumbnailPath(),
                    'private' => $highlight->getVisibility() !== Video::VISIBILITY_PUBLIC,
                ],
            ];
        }

        if ($items !== []) {
            return $items;
        }

        return [
            [
                'user' => 'Youssef',
                'matchId' => 12345,
                'clips' => [
                    ['videoUrl' => 'clip1.mp4', 'thumbnail' => 'clip1.jpg', 'killInfo' => 'Kill 1', 'private' => false],
                    ['videoUrl' => 'clip2.mp4', 'thumbnail' => 'clip2.jpg', 'killInfo' => 'Kill 2', 'private' => true],
                ],
                'highlightVideo' => ['videoUrl' => 'highlight_12345.mp4', 'thumbnail' => 'highlight.jpg', 'private' => false],
            ],
            [
                'user' => 'AutreJoueur',
                'matchId' => 12346,
                'clips' => [
                    ['videoUrl' => 'clip3.mp4', 'thumbnail' => 'clip3.jpg', 'killInfo' => 'Kill 1', 'private' => false],
                ],
                'highlightVideo' => ['videoUrl' => 'highlight_12346.mp4', 'thumbnail' => 'highlight2.jpg', 'private' => false],
            ],
        ];
    }
}
