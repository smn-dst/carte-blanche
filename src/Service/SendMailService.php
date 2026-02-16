<?php

namespace App\Service;

use App\Entity\User;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

readonly class SendMailService
{
    public function __construct(
        private EmailVerifier $emailVerifier, private MailerInterface $mailer,
    ) {
    }

    public function sendVerificationEmail(User $user): void
    {
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            new TemplatedEmail()
                ->from(new Address('no-reply@carte-blanche.test', 'Carte Blanche'))
                ->to($user->getEmail())
                ->subject('Veuillez confirmer votre adresse email')
                ->htmlTemplate('emails/confirmation_email.html.twig')
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendPasswordResetEmail(string $email, string $resetUrl): void
    {
        $message = new TemplatedEmail()
            ->from(new Address('no-reply@carte-blanche.test', 'Carte Blanche'))
            ->to($email)
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('emails/password_reset_email.html.twig')
            ->context([
                'resetUrl' => $resetUrl,
            ]);
        $this->mailer->send($message);
    }
}
