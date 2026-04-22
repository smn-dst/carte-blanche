<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Refund;
use App\Entity\Restaurant;
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
    public function sendPasswordResetEmail(string $email, string $resetUrl, ?string $firstName = null): void
    {
        $message = new TemplatedEmail()
            ->from(new Address(self::SENDER_EMAIL, self::SENDER_NAME))
            ->to($email)
            ->subject('Réinitialisez votre mot de passe — Carte Blanche')
            ->htmlTemplate('emails/password_reset_email.html.twig')
            ->context([
                'resetUrl' => $resetUrl,
                'firstName' => $firstName,
            ]);

        $this->mailer->send($message);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEmailChangeConfirmationEmail(string $email, string $confirmUrl, ?string $firstName = null): void
    {
        $message = new TemplatedEmail()
            ->from(new Address(self::SENDER_EMAIL, self::SENDER_NAME))
            ->to($email)
            ->subject('Confirmez votre nouvelle adresse email — Carte Blanche')
            ->htmlTemplate('emails/change_email_confirmation.html.twig')
            ->context([
                'confirmUrl' => $confirmUrl,
                'firstName' => $firstName,
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

    public function sendRefundApproved(User $user, Refund $refund): void
    {
        $email = $user->getEmail();
        if (null === $email) {
            return;
        }

        $message = (new TemplatedEmail())
            ->from(new Address(self::SENDER_EMAIL, self::SENDER_NAME))
            ->to(new Address($email, ($user->getFirstName() ?? '').' '.($user->getLastName() ?? '')))
            ->subject('Votre remboursement a été approuvé — Carte Blanche')
            ->htmlTemplate('emails/refund_approved.html.twig')
            ->context([
                'user' => $user,
                'refund' => $refund,
                'order' => $refund->getOrder(),
            ]);

        $this->mailer->send($message);
    }

    public function sendRefundRejected(User $user, Refund $refund): void
    {
        $email = $user->getEmail();
        if (null === $email) {
            return;
        }

        $message = (new TemplatedEmail())
            ->from(new Address(self::SENDER_EMAIL, self::SENDER_NAME))
            ->to(new Address($email, ($user->getFirstName() ?? '').' '.($user->getLastName() ?? '')))
            ->subject('Votre demande de remboursement — Carte Blanche')
            ->htmlTemplate('emails/refund_rejected.html.twig')
            ->context([
                'user' => $user,
                'refund' => $refund,
                'order' => $refund->getOrder(),
            ]);

        $this->mailer->send($message);
    }

    public function sendRestaurantApproved(User $user, Restaurant $restaurant): void
    {
        $email = $user->getEmail();
        if (null === $email) {
            return;
        }

        $message = (new TemplatedEmail())
            ->from(new Address(self::SENDER_EMAIL, self::SENDER_NAME))
            ->to(new Address($email, ($user->getFirstName() ?? '').' '.($user->getLastName() ?? '')))
            ->subject(sprintf('Votre restaurant "%s" a été publié — Carte Blanche', $restaurant->getName()))
            ->htmlTemplate('emails/restaurant_approved.html.twig')
            ->context([
                'user' => $user,
                'restaurant' => $restaurant,
            ]);

        $this->mailer->send($message);
    }

    public function sendRestaurantRejected(User $user, Restaurant $restaurant): void
    {
        $email = $user->getEmail();
        if (null === $email) {
            return;
        }

        $message = (new TemplatedEmail())
            ->from(new Address(self::SENDER_EMAIL, self::SENDER_NAME))
            ->to(new Address($email, ($user->getFirstName() ?? '').' '.($user->getLastName() ?? '')))
            ->subject(sprintf('Votre annonce "%s" nécessite des modifications — Carte Blanche', $restaurant->getName()))
            ->htmlTemplate('emails/restaurant_rejected.html.twig')
            ->context([
                'user' => $user,
                'restaurant' => $restaurant,
            ]);

        $this->mailer->send($message);
    }
}
