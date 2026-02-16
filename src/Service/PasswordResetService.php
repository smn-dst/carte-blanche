<?php

namespace App\Service;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class PasswordResetService
{
    public function __construct(
        private readonly SendMailService $sendMailService,
        private readonly UserRepository $userRepository,
        private readonly PasswordResetTokenRepository $passwordResetTokenRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws RandomException
     */
    public function requestReset(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user instanceof User) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $passwordResetToken = new PasswordResetToken($user, $token, $expiresAt);

        $this->entityManager->persist($passwordResetToken);
        $this->entityManager->flush();

        $resetUrl = $this->urlGenerator->generate(
            'app_reset_password',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->sendMailService->sendPasswordResetEmail($user->getEmail(), $resetUrl);
    }

    public function tokenExists(string $token): PasswordResetToken
    {
        $passwordResetToken = $this->passwordResetTokenRepository->findOneBy(['token' => $token]);

        if (!$passwordResetToken instanceof PasswordResetToken) {
            throw new \RuntimeException('Invalid token');
        }

        if ($passwordResetToken->getExpiresAt() < new \DateTimeImmutable()) {
            throw new \RuntimeException('Token expired');
        }

        return $passwordResetToken;
    }

    public function resetPassword(string $token, string $password): void
    {
        $tokenEntity = $this->tokenExists($token);

        $user = $tokenEntity->getOwner();
        if (!$user instanceof User) {
            throw new \RuntimeException('User not found');
        }

        $passwordHasher = $this->userPasswordHasher->hashPassword($user, $password);
        $user->setPassword($passwordHasher);
        $this->entityManager->remove($tokenEntity);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
