<?php

namespace App\Service;

use App\Entity\EmailChangeToken;
use App\Entity\User;
use App\Repository\EmailChangeTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class EmailChangeService
{
    public function __construct(
        private SendMailService $sendMailService,
        private UserRepository $userRepository,
        private EmailChangeTokenRepository $emailChangeTokenRepository,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function requestEmailChange(User $user, string $newEmail): void
    {
        $normalizedEmail = strtolower(trim($newEmail));
        if ('' === $normalizedEmail || false === filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Veuillez renseigner une adresse email valide.');
        }

        $currentEmail = strtolower(trim((string) $user->getEmail()));
        if ($normalizedEmail === $currentEmail) {
            throw new \InvalidArgumentException('Cette adresse email est déjà associée à votre compte.');
        }

        $existingUser = $this->userRepository->findOneBy(['email' => $normalizedEmail]);
        if ($existingUser instanceof User && $existingUser->getId() !== $user->getId()) {
            throw new \InvalidArgumentException('Cette adresse email est déjà utilisée.');
        }

        foreach ($user->getEmailChangeTokens() as $existingToken) {
            $this->entityManager->remove($existingToken);
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $emailChangeToken = new EmailChangeToken($user, $token, $normalizedEmail, $expiresAt);

        $this->entityManager->persist($emailChangeToken);
        $this->entityManager->flush();

        $confirmUrl = $this->urlGenerator->generate(
            'app_profile_confirm_email_change',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->sendMailService->sendEmailChangeConfirmationEmail(
            $normalizedEmail,
            $confirmUrl,
            $user->getFirstName()
        );
    }

    public function confirmEmailChange(string $token): void
    {
        $emailChangeToken = $this->emailChangeTokenRepository->findOneBy(['token' => $token]);
        if (!$emailChangeToken instanceof EmailChangeToken) {
            throw new \RuntimeException('Token invalide.');
        }

        if ($emailChangeToken->getExpiresAt() < new \DateTimeImmutable()) {
            $this->entityManager->remove($emailChangeToken);
            $this->entityManager->flush();
            throw new \RuntimeException('Token expiré.');
        }

        $newEmail = strtolower(trim((string) $emailChangeToken->getNewEmail()));
        if ('' === $newEmail) {
            throw new \RuntimeException('Adresse email invalide.');
        }

        $user = $emailChangeToken->getOwner();
        if (!$user instanceof User) {
            throw new \RuntimeException('Utilisateur introuvable.');
        }

        $existingUser = $this->userRepository->findOneBy(['email' => $newEmail]);
        if ($existingUser instanceof User && $existingUser->getId() !== $user->getId()) {
            $this->entityManager->remove($emailChangeToken);
            $this->entityManager->flush();
            throw new \RuntimeException('Cette adresse email est déjà utilisée.');
        }

        $user->setEmail($newEmail);
        $user->setUpdatedAt(new \DateTimeImmutable());

        foreach ($user->getEmailChangeTokens() as $tokenEntity) {
            $this->entityManager->remove($tokenEntity);
        }

        $this->entityManager->flush();
    }
}
