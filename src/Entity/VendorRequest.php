<?php

namespace App\Entity;

use App\Enum\StatusVendorRequestEnum;
use App\Repository\VendorRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VendorRequestRepository::class)]
class VendorRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: StatusVendorRequestEnum::class, options: ['default' => 'en_attente'])]
    private StatusVendorRequestEnum $status = StatusVendorRequestEnum::EN_ATTENTE;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    /** Pourquoi l'utilisateur veut devenir vendeur */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motivation = null;

    /** Nom du fichier pièce d'identité uploadé */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $idCardFileName = null;

    #[ORM\ManyToOne(inversedBy: 'vendorRequests')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'processed_by_id', referencedColumnName: 'id', nullable: true)]
    private ?User $processedBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): StatusVendorRequestEnum
    {
        return $this->status;
    }

    public function setStatus(StatusVendorRequestEnum $status): static
    {
        $this->status = $status;

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

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): static
    {
        $this->processedAt = $processedAt;

        return $this;
    }

    public function getMotivation(): ?string
    {
        return $this->motivation;
    }

    public function setMotivation(?string $motivation): static
    {
        $this->motivation = $motivation;

        return $this;
    }

    public function getIdCardFileName(): ?string
    {
        return $this->idCardFileName;
    }

    public function setIdCardFileName(?string $idCardFileName): static
    {
        $this->idCardFileName = $idCardFileName;

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

    public function getProcessedBy(): ?User
    {
        return $this->processedBy;
    }

    public function setProcessedBy(?User $processedBy): static
    {
        $this->processedBy = $processedBy;

        return $this;
    }
}
