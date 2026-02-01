<?php

namespace App\Entity;

use App\Repository\CharteAgreementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CharteAgreementRepository::class)]
class CharteAgreement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $sectionName = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $agreedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'charteAgreements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct()
    {
        $this->agreedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSectionName(): ?string
    {
        return $this->sectionName;
    }

    public function setSectionName(string $sectionName): static
    {
        $this->sectionName = $sectionName;
        return $this;
    }

    public function getAgreedAt(): ?\DateTimeImmutable
    {
        return $this->agreedAt;
    }

    public function setAgreedAt(\DateTimeImmutable $agreedAt): static
    {
        $this->agreedAt = $agreedAt;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
}
