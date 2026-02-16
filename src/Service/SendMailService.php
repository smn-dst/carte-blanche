<?php

namespace App\Service;

use App\Entity\User;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

readonly class SendMailService
{
    public function __construct(
        private EmailVerifier $emailVerifier,
    ) {
    }

    public function sendVerificationEmail(User $user): void
    {
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
                ->from(new Address('no-reply@candidate-apps.test', 'Candidate Apps'))
                ->to($user->getEmail())
                ->subject('Veuillez confirmer votre adresse email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
    }
}
