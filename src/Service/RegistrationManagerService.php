<?php

namespace App\Service;

use App\Dto\RegistrationInputDto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

readonly class RegistrationManagerService
{
    public function __construct(
        private SendMailService $sendMailService,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @throws RandomException
     */
    public function register(RegistrationInputDto $registrationInputDto): void
    {
        $user = new User();
        $user->setEmail($registrationInputDto->email);
        $user->setFirstName($registrationInputDto->firstName);
        $user->setLastName($registrationInputDto->lastName);
        $user->setPhoneNumber($registrationInputDto->phoneNumber);
        $user->setRoles(['ROLE_USER']);
        $plainPassword = $registrationInputDto->plainPassword;
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->sendMailService->sendVerificationEmail($user);
    }
}
