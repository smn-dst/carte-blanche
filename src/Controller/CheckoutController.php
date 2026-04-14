<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\StatusOrderEnum;
use App\Repository\OrderRepository;
use App\Service\CheckoutService;
use App\Service\SendMailService;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CheckoutController extends AbstractController
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
    ) {
    }

    #[Route('/checkout', name: 'app_checkout', methods: ['POST'])]
    public function checkout(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('checkout', \is_string($token) ? $token : null)) {
            return $this->redirectToRoute('app_cart');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        try {
            $session = $this->checkoutService->createCheckoutSession($user);
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_cart');
        }

        $checkoutUrl = $session->url;
        if (!\is_string($checkoutUrl) || '' === $checkoutUrl) {
            $this->addFlash('error', 'Impossible d\'obtenir l\'URL de paiement Stripe.');

            return $this->redirectToRoute('app_cart');
        }

        return $this->redirect($checkoutUrl);
    }

    #[Route('/checkout/success', name: 'app_checkout_success', methods: ['GET'])]
    public function success(Request $request, OrderRepository $orderRepository, SendMailService $sendMailService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $sessionId = $request->query->get('session_id');
        if (null === $sessionId || '' === $sessionId) {
            return $this->redirectToRoute('app_cart');
        }

        $order = $orderRepository->findOneBy(['stripeSessionId' => $sessionId]);

        // Fallback : si le webhook n'a pas encore traité la commande (dev local, race condition)
        if (null !== $order && StatusOrderEnum::EN_ATTENTE === $order->getStatus()) {
            try {
                $order = $this->checkoutService->fulfillOrder($sessionId);
            } catch (\Throwable $e) {
                error_log('FULFILL ERROR in success: '.$e::class.': '.$e->getMessage());
            }

            $buyer = $order->getBuyer();
            if (null !== $buyer) {
                try {
                    $sendMailService->sendOrderConfirmationEmail($buyer, $order);
                } catch (\Throwable $e) {
                    error_log('EMAIL ERROR in success: '.$e::class.': '.$e->getMessage());
                }
            }
        }

        return $this->render('checkout/success.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/checkout/annulation', name: 'app_checkout_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        $this->addFlash('error', 'Paiement annulé. Votre panier est conservé.');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/webhook/stripe', name: 'app_stripe_webhook', methods: ['POST'])]
    public function webhook(Request $request, SendMailService $sendMailService): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');
        $webhookSecret = $this->getParameter('stripe_webhook_secret');
        $stripeSecretKey = $this->getParameter('stripe_secret_key');

        try {
            Stripe::setApiKey(\is_string($stripeSecretKey) ? $stripeSecretKey : '');
            $event = Webhook::constructEvent($payload, $sigHeader ?? '', \is_string($webhookSecret) ? $webhookSecret : '');
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        if ('checkout.session.completed' === $event->type) {
            /** @var \Stripe\Checkout\Session $session */
            $session = $event->data->object;

            try {
                $order = $this->checkoutService->fulfillOrder($session->id);
            } catch (\Throwable $e) {
                error_log('WEBHOOK FULFILL ERROR: '.$e::class.': '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());
                return new JsonResponse(['error' => $e->getMessage()], 500);
            }

            $buyer = $order->getBuyer();
            if (null !== $buyer) {
                try {
                    $sendMailService->sendOrderConfirmationEmail($buyer, $order);
                } catch (\Throwable $e) {
                    error_log('WEBHOOK EMAIL ERROR: '.$e::class.': '.$e->getMessage());
                }
            }
        }

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/ticket/verify/{uuid}', name: 'app_ticket_verify', methods: ['GET'])]
    public function verifyTicket(string $uuid): Response
    {
        // TODO : page de vérification du ticket par le vendeur
        return $this->render('checkout/verify_ticket.html.twig', [
            'uuid' => $uuid,
        ]);
    }
}
