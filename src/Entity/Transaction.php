<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'boolean')]
    private bool $giverValidated = false;

    #[ORM\Column(type: 'boolean')]
    private bool $receiverValidated = false;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rating = null;

    #[ORM\OneToOne(targetEntity: Annonce::class, inversedBy: 'transaction')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?Annonce $annonce = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $receiver = null;

    public function __construct()
    {
        $this->giverValidated = false;
        $this->receiverValidated = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isGiverValidated(): bool
    {
        return $this->giverValidated;
    }

    public function setGiverValidated(bool $giverValidated): static
    {
        $this->giverValidated = $giverValidated;
        return $this;
    }

    public function isReceiverValidated(): bool
    {
        return $this->receiverValidated;
    }

    public function setReceiverValidated(bool $receiverValidated): static
    {
        $this->receiverValidated = $receiverValidated;
        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): static
    {
        $this->rating = $rating;
        return $this;
    }

    public function getAnnonce(): ?Annonce
    {
        return $this->annonce;
    }

    public function setAnnonce(?Annonce $annonce): static
    {
        $this->annonce = $annonce;
        return $this;
    }

    public function getReceiver(): ?User
    {
        return $this->receiver;
    }

    public function setReceiver(?User $receiver): static
    {
        $this->receiver = $receiver;
        return $this;
    }
}
