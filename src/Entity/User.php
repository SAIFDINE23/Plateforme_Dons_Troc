<?php

namespace App\Entity;

use App\Enum\Campus;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $casUid = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string', enumType: Campus::class, nullable: true)]
    private ?Campus $moderatedCampus = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isBanned = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $password = null;

    /**
     * @var Collection<int, CharteAgreement>
     */
    #[ORM\OneToMany(targetEntity: CharteAgreement::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $charteAgreements;

    /**
     * @var Collection<int, Annonce>
     */
    #[ORM\OneToMany(targetEntity: Annonce::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $annonces;

    /**
     * @var Collection<int, Conversation>
     */
    #[ORM\OneToMany(targetEntity: Conversation::class, mappedBy: 'buyer')]
    private Collection $conversations;

    /**
     * @var Collection<int, Conversation>
     */
    #[ORM\ManyToMany(targetEntity: Conversation::class, mappedBy: 'participants')]
    private Collection $participatingConversations;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'sender')]
    private Collection $messages;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $notifications;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'receiver')]
    private Collection $transactions;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->charteAgreements = new ArrayCollection();
        $this->annonces = new ArrayCollection();
        $this->conversations = new ArrayCollection();
        $this->participatingConversations = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getCasUid(): ?string
    {
        return $this->casUid;
    }

    public function setCasUid(string $casUid): static
    {
        $this->casUid = $casUid;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->casUid;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getModeratedCampus(): ?Campus
    {
        return $this->moderatedCampus;
    }

    public function setModeratedCampus(?Campus $moderatedCampus): static
    {
        $this->moderatedCampus = $moderatedCampus;
        return $this;
    }

    public function isBanned(): bool
    {
        return $this->isBanned;
    }

    public function setIsBanned(bool $isBanned): static
    {
        $this->isBanned = $isBanned;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Collection<int, CharteAgreement>
     */
    public function getCharteAgreements(): Collection
    {
        return $this->charteAgreements;
    }

    public function addCharteAgreement(CharteAgreement $charteAgreement): static
    {
        if (!$this->charteAgreements->contains($charteAgreement)) {
            $this->charteAgreements->add($charteAgreement);
            $charteAgreement->setUser($this);
        }
        return $this;
    }

    public function removeCharteAgreement(CharteAgreement $charteAgreement): static
    {
        if ($this->charteAgreements->removeElement($charteAgreement)) {
            if ($charteAgreement->getUser() === $this) {
                $charteAgreement->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Annonce>
     */
    public function getAnnonces(): Collection
    {
        return $this->annonces;
    }

    public function addAnnonce(Annonce $annonce): static
    {
        if (!$this->annonces->contains($annonce)) {
            $this->annonces->add($annonce);
            $annonce->setOwner($this);
        }
        return $this;
    }

    public function removeAnnonce(Annonce $annonce): static
    {
        if ($this->annonces->removeElement($annonce)) {
            if ($annonce->getOwner() === $this) {
                $annonce->setOwner(null);
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
            $conversation->setBuyer($this);
        }
        return $this;
    }

    public function removeConversation(Conversation $conversation): static
    {
        if ($this->conversations->removeElement($conversation)) {
            if ($conversation->getBuyer() === $this) {
                $conversation->setBuyer(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function getParticipatingConversations(): Collection
    {
        return $this->participatingConversations;
    }

    public function addParticipatingConversation(Conversation $conversation): static
    {
        if (!$this->participatingConversations->contains($conversation)) {
            $this->participatingConversations->add($conversation);
            $conversation->addParticipant($this);
        }
        return $this;
    }

    public function removeParticipatingConversation(Conversation $conversation): static
    {
        if ($this->participatingConversations->removeElement($conversation)) {
            $conversation->removeParticipant($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setSender($this);
        }
        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getSender() === $this) {
                $message->setSender(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }
        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setReceiver($this);
        }
        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            if ($transaction->getReceiver() === $this) {
                $transaction->setReceiver(null);
            }
        }
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Nothing to erase for CAS authentication
    }
}
