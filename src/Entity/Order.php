<?php

namespace App\Entity;

use App\Enum\StatusOrderEnum;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalAmount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeSessionId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(enumType: StatusOrderEnum::class, options: ['default' => 'en_attente'])]
    private StatusOrderEnum $status = StatusOrderEnum::EN_ATTENTE;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(name: 'buyer_id', referencedColumnName: 'id', nullable: false)]
    private ?User $buyer = null;

    /**
     * @var Collection<int, Ticket>
     */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: Ticket::class)]
    private Collection $tickets;

    /**
     * @var Collection<int, Refund>
     */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: Refund::class)]
    private Collection $refunds;

    public function __construct()
    {
        $this->tickets = new ArrayCollection();
        $this->refunds = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): static
    {
        $this->stripeSessionId = $stripeSessionId;

        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): static
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;

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

    public function getStatus(): StatusOrderEnum
    {
        return $this->status;
    }

    public function setStatus(StatusOrderEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getBuyer(): ?User
    {
        return $this->buyer;
    }

    public function setBuyer(?User $buyer): static
    {
        $this->buyer = $buyer;

        return $this;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): static
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $ticket->setOrder($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): static
    {
        if ($this->tickets->removeElement($ticket)) {
            if ($ticket->getOrder() === $this) {
                $ticket->setOrder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Refund>
     */
    public function getRefunds(): Collection
    {
        return $this->refunds;
    }

    public function addRefund(Refund $refund): static
    {
        if (!$this->refunds->contains($refund)) {
            $this->refunds->add($refund);
            $refund->setOrder($this);
        }

        return $this;
    }

    public function removeRefund(Refund $refund): static
    {
        if ($this->refunds->removeElement($refund)) {
            if ($refund->getOrder() === $this) {
                $refund->setOrder(null);
            }
        }

        return $this;
    }
}
