<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\Ticket;
use App\Entity\User;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

readonly class SendMailService
{
    private const SENDER_EMAIL = 'simon.dousset@ecole-decode.fr';
    private const SENDER_NAME = 'Carte Blanche';

    public function __construct(
        private EmailVerifier $emailVerifier,
        private MailerInterface $mailer,
        private OrderTicketsPdfGenerator $orderTicketsPdfGenerator,
    ) {
    }

    public function sendVerificationEmail(User $user): void
    {
        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            new TemplatedEmail()
                ->from(new Address(self::SENDER_EMAIL, self::SENDER_NAME))
                ->to($user->getEmail() ?? throw new \InvalidArgumentException('User email is required.'))
                ->subject('Confirmez votre adresse email — Carte Blanche')
                ->htmlTemplate('emails/confirmation_email.html.twig')
                ->context(['user' => $user])
        );
    }

    public function sendVendorRequestCreated(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address(self::SENDER_EMAIL, self::SENDER_NAME))
            ->to($user->getEmail() ?? throw new \InvalidArgumentException('User email is required.'))
            ->subject('Votre demande vendeur a bien été reçue')
            ->htmlTemplate('emails/vendor_request_created.html.twig')
            ->context(['user' => $user]);

        $this->mailer->send($email);
    }

    public function sendVendorRequestApproved(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address(self::SENDER_EMAIL, self::SENDER_NAME))
            ->to($user->getEmail() ?? throw new \InvalidArgumentException('User email is required.'))
            ->subject('Votre demande vendeur a été approuvée')
            ->htmlTemplate('emails/vendor_request_approved.html.twig')
            ->context(['user' => $user]);

        $this->mailer->send($email);
    }

    public function sendVendorRequestRefused(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address(self::SENDER_EMAIL, self::SENDER_NAME))
            ->to($user->getEmail() ?? throw new \InvalidArgumentException('User email is required.'))
            ->subject('Votre demande vendeur a été refusée')
            ->htmlTemplate('emails/vendor_request_refused.html.twig')
            ->context(['user' => $user]);

        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendPasswordResetEmail(string $email, string $resetUrl): void
    {
        $message = new TemplatedEmail()
            ->from(new Address(self::SENDER_EMAIL, self::SENDER_NAME))
            ->to($email)
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('emails/password_reset_email.html.twig')
            ->context(['resetUrl' => $resetUrl]);

        $this->mailer->send($message);
    }

    public function sendAbandonedCartEmail(User $user, Cart $cart): void
    {
        $email = $user->getEmail();
        if (null === $email) {
            return;
        }

        $total = 0;
        foreach ($cart->getCartItems() as $item) {
            $restaurant = $item->getRestaurant();
            if (null !== $restaurant) {
                $total += ((float) ($restaurant->getTicketPrice() ?? 0)) * $item->getQuantity();
            }
        }

        $message = (new TemplatedEmail())
            ->from(new Address(self::SENDER_EMAIL, self::SENDER_NAME))
            ->to(new Address($email, ($user->getFirstName() ?? '').' '.($user->getLastName() ?? '')))
            ->subject('Vous avez oublié quelque chose dans votre panier !')
            ->htmlTemplate('emails/abandoned_cart.html.twig')
            ->context([
                'user' => $user,
                'cartItems' => $cart->getCartItems(),
                'total' => $total,
            ]);

        $this->mailer->send($message);
    }

    public function sendEventReminderEmail(User $user, Ticket $ticket): void
    {
        $email = $user->getEmail();
        if (null === $email) {
            return;
        }

        $message = (new TemplatedEmail())
            ->from(new Address(self::SENDER_EMAIL, self::SENDER_NAME))
            ->to(new Address($email, ($user->getFirstName() ?? '').' '.($user->getLastName() ?? '')))
            ->subject('Rappel qu\une enchère va avoir lieu dans 7 jours !')
            ->htmlTemplate('emails/event_reminder.html.twig')
            ->context([
                'user' => $user,
                'ticket' => $ticket,
                'restaurant' => $ticket->getRestaurant(),
            ]);

        $this->mailer->send($message);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendOrderConfirmationEmail(User $user, Order $order): void
    {
        $email = $user->getEmail();
        if (null === $email) {
            return;
        }

        $message = new TemplatedEmail()
            ->from(new Address(self::SENDER_EMAIL, self::SENDER_NAME))
            ->to(new Address($email, ($user->getFirstName() ?? '').' '.($user->getLastName() ?? '')))
            ->subject('Confirmation de commande '.($order->getReference() ?? ''))
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->context([
                'user' => $user,
                'order' => $order,
            ]);

        if ($order->getTickets()->count() > 0) {
            try {
                $pdf = $this->orderTicketsPdfGenerator->generate($order);
                $ref = (string) ($order->getReference() ?? 'commande');
                $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $ref);
                if ('' === $safeName) {
                    $safeName = 'commande';
                }
                $message->attach($pdf, 'billets-'.$safeName.'.pdf', 'application/pdf');
            } catch (\Throwable) {
                // PDF optionnel : l’e-mail HTML part quand même
            }
        }

        $this->mailer->send($message);
    }
}
