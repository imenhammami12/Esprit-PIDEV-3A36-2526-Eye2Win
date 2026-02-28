<?php

namespace App\Entity;

use App\Repository\VideoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VideoRepository::class)]
class Video
{
    public const TYPE_UPLOAD = 'UPLOAD';
    public const TYPE_CLIP = 'CLIP';
    public const TYPE_HIGHLIGHT = 'HIGHLIGHT';

    public const VISIBILITY_PRIVATE = 'PRIVATE';
    public const VISIBILITY_PUBLIC = 'PUBLIC';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $gameType = null;

    #[ORM\Column(length: 255)]
    private ?string $filePath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $publicId = null;

    #[ORM\Column(nullable: true)]
    private ?float $duration = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $resolution = null;

    #[ORM\Column(nullable: true)]
    private ?float $fps = null;

    #[ORM\Column]
    private ?\DateTime $uploadedAt = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(length: 10)]
    private ?string $visibility = 'PRIVATE';

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_UPLOAD;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $matchExternalId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $thumbnailPath = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $killInfo = null;

    #[ORM\Column]
    private int $likesCount = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $metadataJson = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'clips')]
    private ?self $highlight = null;

    /**
     * @var Collection<int, Video>
     */
    #[ORM\OneToMany(mappedBy: 'highlight', targetEntity: self::class)]
    private Collection $clips;

    #[ORM\ManyToOne(inversedBy: 'videos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $uploadedBy = null;

    /**
     * @var Collection<int, PlayerStat>
     */
    #[ORM\OneToMany(targetEntity: PlayerStat::class, mappedBy: 'videomatch', orphanRemoval: true)]
    private Collection $playerStats;

    public function __construct()
    {
        $this->playerStats = new ArrayCollection();
        $this->visibility = self::VISIBILITY_PRIVATE;
        $this->clips = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getGameType(): ?string
    {
        return $this->gameType;
    }

    public function setGameType(string $gameType): static
    {
        $this->gameType = $gameType;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getVideoUrl(): ?string
    {
        return $this->filePath;
    }

    public function setVideoUrl(string $videoUrl): static
    {
        $this->filePath = $videoUrl;

        return $this;
    }

    public function getPublicId(): ?string
    {
        return $this->publicId;
    }

    public function setPublicId(?string $publicId): static
    {
        $this->publicId = $publicId;

        return $this;
    }

    public function getDuration(): ?float
    {
        return $this->duration;
    }

    public function setDuration(?float $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getResolution(): ?string
    {
        return $this->resolution;
    }

    public function setResolution(?string $resolution): static
    {
        $this->resolution = $resolution;

        return $this;
    }

    public function getFps(): ?float
    {
        return $this->fps;
    }

    public function setFps(?float $fps): static
    {
        $this->fps = $fps;

        return $this;
    }

    public function getUploadedAt(): ?\DateTime
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTime $uploadedAt): static
    {
        $this->uploadedAt = $uploadedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->uploadedAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->uploadedAt = $createdAt;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getVisibility(): ?string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getMatchExternalId(): ?string
    {
        return $this->matchExternalId;
    }

    public function setMatchExternalId(?string $matchExternalId): static
    {
        $this->matchExternalId = $matchExternalId;

        return $this;
    }

    public function getThumbnailPath(): ?string
    {
        return $this->thumbnailPath;
    }

    public function setThumbnailPath(?string $thumbnailPath): static
    {
        $this->thumbnailPath = $thumbnailPath;

        return $this;
    }

    public function getKillInfo(): ?string
    {
        return $this->killInfo;
    }

    public function setKillInfo(?string $killInfo): static
    {
        $this->killInfo = $killInfo;

        return $this;
    }

    public function getLikesCount(): int
    {
        return $this->likesCount;
    }

    public function setLikesCount(int $likesCount): static
    {
        $this->likesCount = max(0, $likesCount);

        return $this;
    }

    public function incrementLikes(): static
    {
        ++$this->likesCount;

        return $this;
    }

    public function getMetadata(): ?array
    {
        if ($this->metadataJson === null || $this->metadataJson === '') {
            return null;
        }

        $decoded = json_decode($this->metadataJson, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadataJson = $metadata === null ? null : json_encode($metadata);

        return $this;
    }

    public function getHighlight(): ?self
    {
        return $this->highlight;
    }

    public function setHighlight(?self $highlight): static
    {
        $this->highlight = $highlight;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getClips(): Collection
    {
        return $this->clips;
    }

    public function addClip(self $clip): static
    {
        if (!$this->clips->contains($clip)) {
            $this->clips->add($clip);
            $clip->setHighlight($this);
        }

        return $this;
    }

    public function removeClip(self $clip): static
    {
        if ($this->clips->removeElement($clip) && $clip->getHighlight() === $this) {
            $clip->setHighlight(null);
        }

        return $this;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): static
    {
        $this->uploadedBy = $uploadedBy;

        return $this;
    }

    /**
     * @return Collection<int, PlayerStat>
     */
    public function getPlayerStats(): Collection
    {
        return $this->playerStats;
    }

    public function addPlayerStat(PlayerStat $playerStat): static
    {
        if (!$this->playerStats->contains($playerStat)) {
            $this->playerStats->add($playerStat);
            $playerStat->setVideomatch($this);
        }

        return $this;
    }

    public function removePlayerStat(PlayerStat $playerStat): static
    {
        if ($this->playerStats->removeElement($playerStat)) {
            // set the owning side to null (unless already changed)
            if ($playerStat->getVideomatch() === $this) {
                $playerStat->setVideomatch(null);
            }
        }

        return $this;
    }
}
