<?php

namespace App\Service;

use App\Entity\Order;
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
