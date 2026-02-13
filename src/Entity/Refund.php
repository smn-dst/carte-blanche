<?php

namespace App\Entity;

use App\Enum\StatusRefundEnum;
use App\Repository\RefundRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RefundRepository::class)]
class Refund
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $reasonRefund = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeRefundId = null;

    #[ORM\Column(enumType: StatusRefundEnum::class, options: ['default' => 'en_attente'])]
    private StatusRefundEnum $status = StatusRefundEnum::EN_ATTENTE;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'refunds')]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', nullable: false)]
    private ?Order $order = null;

    #[ORM\ManyToOne(inversedBy: 'refundsRequested')]
    #[ORM\JoinColumn(name: 'requested_by_id', referencedColumnName: 'id', nullable: false)]
    private ?User $requestedBy = null;

    #[ORM\ManyToOne(inversedBy: 'refundsProcessed')]
    #[ORM\JoinColumn(name: 'processed_by_id', referencedColumnName: 'id', nullable: true)]
    private ?User $processedBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getReasonRefund(): ?string
    {
        return $this->reasonRefund;
    }

    public function setReasonRefund(string $reasonRefund): static
    {
        $this->reasonRefund = $reasonRefund;

        return $this;
    }

    public function getStripeRefundId(): ?string
    {
        return $this->stripeRefundId;
    }

    public function setStripeRefundId(?string $stripeRefundId): static
    {
        $this->stripeRefundId = $stripeRefundId;

        return $this;
    }

    public function getStatus(): StatusRefundEnum
    {
        return $this->status;
    }

    public function setStatus(StatusRefundEnum $status): static
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

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getRequestedBy(): ?User
    {
        return $this->requestedBy;
    }

    public function setRequestedBy(?User $requestedBy): static
    {
        $this->requestedBy = $requestedBy;

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
