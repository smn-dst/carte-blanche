<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\StatusOrderEnum;
use App\Enum\StatusTicketEnum;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Stripe\Checkout\Session as StripeSession;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CheckoutService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CartService $cartService,
        private readonly string $stripeSecretKey,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $qrCodeDirectory,
    ) {
    }

    /**
     * Crée une Stripe Checkout Session à partir du panier.
     */
    public function createCheckoutSession(User $user): StripeSession
    {
        Stripe::setApiKey($this->stripeSecretKey);

        $cart = $this->cartService->getOrCreateCart($user);
        $lineItems = [];

        foreach ($cart->getCartItems() as $item) {
            $restaurant = $item->getRestaurant();
            if (null === $restaurant || null === $restaurant->getTicketPrice()) {
                continue;
            }

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $restaurant->getName() ?? 'Restaurant',
                        'description' => 'Enchère — '.$item->getQuantity().' place(s)',
                    ],
                    'unit_amount' => (int) round((float) $restaurant->getTicketPrice() * 100),
                ],
                'quantity' => $item->getQuantity(),
            ];
        }

        if ([] === $lineItems) {
            throw new \LogicException('Le panier est vide.');
        }

        // Créer l'Order en base (EN_ATTENTE)
        $order = new Order();
        $order->setReference('CB-'.strtoupper(bin2hex(random_bytes(6))));
        $order->setTotalAmount((string) $this->cartService->getCartTotal($cart));
        $order->setBuyer($user);
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setStatus(StatusOrderEnum::EN_ATTENTE);
        $this->em->persist($order);
        $this->em->flush();

        $successUrl = $this->urlGenerator->generate(
            'app_checkout_success',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        ).'?session_id={CHECKOUT_SESSION_ID}';

        $cancelUrl = $this->urlGenerator->generate(
            'app_checkout_cancel',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $customerEmail = $user->getEmail();
        if (!\is_string($customerEmail) || '' === $customerEmail) {
            throw new \LogicException('Adresse e-mail utilisateur manquante pour le paiement.');
        }

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'customer_email' => $customerEmail,
            'client_reference_id' => (string) $order->getId(),
            'metadata' => [
                'order_id' => (string) $order->getId(),
                'user_id' => (string) $user->getId(),
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        $order->setStripeSessionId($session->id);
        $this->em->flush();

        return $session;
    }

    /**
     * Appelé par le webhook Stripe après paiement réussi.
     * Crée les tickets, génère les QR codes, met à jour les stocks.
     */
    public function fulfillOrder(string $sessionId): Order
    {
        Stripe::setApiKey($this->stripeSecretKey);

        $session = StripeSession::retrieve($sessionId);
        $orderId = (int) ($session->metadata->order_id ?? 0);

        $order = $this->em->getRepository(Order::class)->find($orderId);
        if (null === $order) {
            throw new \RuntimeException('Order non trouvée : '.$orderId);
        }

        // Éviter le double traitement
        if (StatusOrderEnum::PAYEE === $order->getStatus()) {
            return $order;
        }

        $order->setStatus(StatusOrderEnum::PAYEE);
        $order->setStripePaymentIntentId($this->paymentIntentIdToString($session->payment_intent));

        $buyer = $order->getBuyer();
        if (null === $buyer) {
            throw new \RuntimeException('Buyer non trouvé pour l\'order '.$orderId);
        }

        $cart = $buyer->getCart();
        if (null === $cart) {
            throw new \RuntimeException('Cart non trouvé pour l\'user '.$buyer->getId());
        }

        // Créer les tickets pour chaque item du panier
        foreach ($cart->getCartItems() as $item) {
            $restaurant = $item->getRestaurant();
            if (null === $restaurant) {
                continue;
            }

            for ($i = 0; $i < $item->getQuantity(); ++$i) {
                $ticket = new Ticket();
                $ticket->setOrder($order);
                $ticket->setRestaurant($restaurant);
                $ticket->setStatus(StatusTicketEnum::VALIDE);
                $ticket->setCreatedAt(new \DateTimeImmutable());

                // Générer le QR code
                $ticketUuid = bin2hex(random_bytes(16));
                $qrContent = $this->urlGenerator->generate('app_ticket_verify', [
                    'uuid' => $ticketUuid,
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $qrFileName = $ticketUuid.'.png';
                $this->generateQrCode($qrContent, $qrFileName);
                $ticket->setQrCode($qrFileName);

                $this->em->persist($ticket);

                // Mettre à jour le compteur de tickets vendus
                $restaurant->setTicketsSold($restaurant->getTicketsSold() + 1);
            }
        }

        $this->em->flush();

        // Vider le panier
        $this->cartService->clearCart($cart);

        return $order;
    }

    private function generateQrCode(string $content, string $fileName): void
    {
        if (!is_dir($this->qrCodeDirectory)) {
            mkdir($this->qrCodeDirectory, 0o755, true);
        }

        $qrCode = new QrCode(
            data: $content,
            size: 400,
            margin: 20,
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        $result->saveToFile($this->qrCodeDirectory.'/'.$fileName);
    }

    /**
     * L’API / le SDK peuvent renvoyer payment_intent comme id (string) ou objet PaymentIntent.
     */
    private function paymentIntentIdToString(string|PaymentIntent|null $value): ?string
    {
        if ($value instanceof PaymentIntent) {
            return $value->id;
        }

        return \is_string($value) && '' !== $value ? $value : null;
    }
}
