<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Refund;
use App\Entity\User;
use App\Enum\StatusOrderEnum;
use App\Enum\StatusRefundEnum;
use App\Enum\StatusTicketEnum;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;

class RefundService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly string $stripeSecretKey,
    ) {
    }

    /**
     * L'acheteur demande un remboursement.
     */
    public function requestRefund(User $user, Order $order, string $reason): Refund
    {
        if ($order->getBuyer()?->getId() !== $user->getId()) {
            throw new \LogicException('Cette commande ne vous appartient pas.');
        }

        if (StatusOrderEnum::PAYEE !== $order->getStatus()) {
            throw new \LogicException('Seules les commandes payées peuvent être remboursées.');
        }

        // Vérifier qu'il n'y a pas déjà une demande en attente
        foreach ($order->getRefunds() as $existingRefund) {
            if (StatusRefundEnum::EN_ATTENTE === $existingRefund->getStatus()) {
                throw new \LogicException('Une demande de remboursement est déjà en cours pour cette commande.');
            }
        }

        $refund = new Refund();
        $refund->setOrder($order);
        $refund->setRequestedBy($user);
        $refund->setAmount($order->getTotalAmount() ?? '0');
        $refund->setReasonRefund($reason);
        $refund->setStatus(StatusRefundEnum::EN_ATTENTE);
        $refund->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($refund);
        $this->em->flush();

        return $refund;
    }

    /**
     * L'admin approuve le remboursement → appel Stripe Refund API.
     */
    public function approveRefund(Refund $refund, User $admin): void
    {
        if (StatusRefundEnum::EN_ATTENTE !== $refund->getStatus()) {
            throw new \LogicException('Ce remboursement a déjà été traité.');
        }

        $order = $refund->getOrder();
        if (null === $order) {
            throw new \LogicException('Commande introuvable.');
        }

        $paymentIntentId = $order->getStripePaymentIntentId();
        if (null === $paymentIntentId || '' === $paymentIntentId) {
            throw new \LogicException('Pas de payment intent Stripe pour cette commande.');
        }

        // Appel Stripe Refund
        Stripe::setApiKey($this->stripeSecretKey);

        $stripeRefund = \Stripe\Refund::create([
            'payment_intent' => $paymentIntentId,
            'amount' => (int) round((float) $refund->getAmount() * 100),
        ]);

        $refund->setStripeRefundId($stripeRefund->id);
        $refund->setStatus(StatusRefundEnum::TRAITE);
        $refund->setProcessedBy($admin);

        // Mettre à jour le statut de la commande
        $totalRefunded = 0;
        foreach ($order->getRefunds() as $r) {
            if (StatusRefundEnum::TRAITE === $r->getStatus()) {
                $totalRefunded += (float) $r->getAmount();
            }
        }

        if ($totalRefunded >= (float) $order->getTotalAmount()) {
            $order->setStatus(StatusOrderEnum::REMBOURSEE);
        } else {
            $order->setStatus(StatusOrderEnum::REMBOURSEMENT_PARTIEL);
        }

        // Expirer les tickets
        foreach ($order->getTickets() as $ticket) {
            $ticket->setStatus(StatusTicketEnum::EXPIRE);

            // Décrémenter ticketsSold
            $restaurant = $ticket->getRestaurant();
            if (null !== $restaurant) {
                $restaurant->setTicketsSold(max(0, $restaurant->getTicketsSold() - 1));
            }
        }

        $this->em->flush();
    }

    /**
     * L'admin refuse le remboursement.
     */
    public function rejectRefund(Refund $refund, User $admin): void
    {
        if (StatusRefundEnum::EN_ATTENTE !== $refund->getStatus()) {
            throw new \LogicException('Ce remboursement a déjà été traité.');
        }

        $refund->setStatus(StatusRefundEnum::REFUSE);
        $refund->setProcessedBy($admin);

        $this->em->flush();
    }
}
