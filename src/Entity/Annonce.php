<?php

namespace App\Entity;

use App\Enum\AnnonceState;
use App\Enum\AnnonceType;
use App\Enum\Campus;
use App\Repository\AnnonceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AnnonceRepository::class)]
class Annonce
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    #[Assert\Length(
        max: 2000,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $description = null;

    #[ORM\Column(type: 'string', enumType: AnnonceType::class)]
    private ?AnnonceType $type = null;

    #[ORM\Column(type: 'string', enumType: AnnonceState::class)]
    private ?AnnonceState $state = null;

    /**
     * @var array<string>
     */
    #[ORM\Column(type: Types::JSON)]
    #[Assert\NotBlank(message: 'Au moins un campus doit être sélectionné')]
    #[Assert\Count(
        min: 1,
        minMessage: 'Au moins un campus doit être sélectionné'
    )]
    private array $campuses = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'annonces')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'annonces')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    /**
     * @var Collection<int, AnnonceImage>
     */
    #[ORM\OneToMany(targetEntity: AnnonceImage::class, mappedBy: 'annonce', orphanRemoval: true, cascade: ['persist'])]
    #[Assert\Count(
        max: 6,
        maxMessage: 'Vous ne pouvez pas ajouter plus de {{ limit }} images'
    )]
    private Collection $images;

    /**
     * @var Collection<int, Conversation>
     */
    #[ORM\OneToMany(targetEntity: Conversation::class, mappedBy: 'annonce')]
    private Collection $conversations;

    #[ORM\OneToOne(targetEntity: Transaction::class, mappedBy: 'annonce', cascade: ['persist', 'remove'])]
    private ?Transaction $transaction = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $refusalReason = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $lockedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lockedAt = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $customCategoryName = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->state = AnnonceState::DRAFT;
        $this->createdAt = new \DateTime();
        $this->images = new ArrayCollection();
        $this->conversations = new ArrayCollection();
    }

    public function getId(): ?Uuid
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getType(): ?AnnonceType
    {
        return $this->type;
    }

    public function setType(AnnonceType $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getState(): ?AnnonceState
    {
        return $this->state;
    }

    public function setState(AnnonceState $state): static
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @return array<string>
     */
    public function getCampuses(): array
    {
        return $this->campuses;
    }

    /**
     * @param array<string> $campuses
     */
    public function setCampuses(array $campuses): static
    {
        $this->campuses = $campuses;
        return $this;
    }

    /**
     * Add a campus to the list
     */
    public function addCampus(string $campus): static
    {
        if (!in_array($campus, $this->campuses)) {
            $this->campuses[] = $campus;
        }
        return $this;
    }

    /**
     * Remove a campus from the list
     */
    public function removeCampus(string $campus): static
    {
        $this->campuses = array_filter($this->campuses, fn($c) => $c !== $campus);
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return Collection<int, AnnonceImage>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(AnnonceImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setAnnonce($this);
        }
        return $this;
    }

    public function removeImage(AnnonceImage $image): static
    {
        if ($this->images->removeElement($image)) {
            if ($image->getAnnonce() === $this) {
                $image->setAnnonce(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function getConversations(): Collection
    {
        return $this->conversations;
    }

    public function addConversation(Conversation $conversation): static
    {
        if (!$this->conversations->contains($conversation)) {
            $this->conversations->add($conversation);
            $conversation->setAnnonce($this);
        }
        return $this;
    }

    public function removeConversation(Conversation $conversation): static
    {
        if ($this->conversations->removeElement($conversation)) {
            if ($conversation->getAnnonce() === $this) {
                $conversation->setAnnonce(null);
            }
        }
        return $this;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(?Transaction $transaction): static
    {
        if ($transaction === null && $this->transaction !== null) {
            $this->transaction->setAnnonce(null);
        }

        if ($transaction !== null && $transaction->getAnnonce() !== $this) {
            $transaction->setAnnonce($this);
        }

        $this->transaction = $transaction;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getRefusalReason(): ?string
    {
        return $this->refusalReason;
    }

    public function setRefusalReason(?string $refusalReason): static
    {
        $this->refusalReason = $refusalReason;

        return $this;
    }

    public function getLockedBy(): ?User
    {
        return $this->lockedBy;
    }

    public function setLockedBy(?User $lockedBy): static
    {
        $this->lockedBy = $lockedBy;
        return $this;
    }

    public function getLockedAt(): ?\DateTimeImmutable
    {
        return $this->lockedAt;
    }

    public function setLockedAt(?\DateTimeImmutable $lockedAt): static
    {
        $this->lockedAt = $lockedAt;
        return $this;
    }

    /**
     * Vérifie si l'annonce est verrouillée
     */
    public function isLocked(): bool
    {
        return $this->lockedBy !== null && $this->lockedAt !== null;
    }

    /**
     * Vérifie si l'annonce est verrouillée par un utilisateur spécifique
     */
    public function isLockedBy(User $user): bool
    {
        return $this->lockedBy !== null && $this->lockedBy->getId()->equals($user->getId());
    }

    /**
     * Vérifie si le verrou a expiré (plus de 30 minutes)
     */
    public function isLockExpired(): bool
    {
        if (!$this->isLocked()) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $lockDuration = $now->getTimestamp() - $this->lockedAt->getTimestamp();
        
        // 30 minutes = 1800 secondes
        return $lockDuration > 1800;
    }

    /**
     * Verrouille l'annonce pour un utilisateur
     */
    public function lock(User $user): void
    {
        $this->lockedBy = $user;
        $this->lockedAt = new \DateTimeImmutable();
    }

    /**
     * Déverrouille l'annonce
     */
    public function unlock(): void
    {
        $this->lockedBy = null;
        $this->lockedAt = null;
    }

    public function getCustomCategoryName(): ?string
    {
        return $this->customCategoryName;
    }

    public function setCustomCategoryName(?string $customCategoryName): static
    {
        $this->customCategoryName = $customCategoryName;
        return $this;
    }
}
