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
        // Here you would typically create a new User entity, hash the password, and save it to the database.
        // For demonstration purposes, we'll skip those steps.
        $user = new User();
        $user->setEmail($registrationInputDto->email);
        $user->setFirstName($registrationInputDto->firstName);
        $user->setLastName($registrationInputDto->lastName);
        $user->setPhoneNumber($registrationInputDto->phoneNumber);
        $user->setRoles($registrationInputDto->roles);
        $plainPassword = $registrationInputDto->plainPassword;
        // Here you would hash the password and set it on the user entity.
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // After saving the user, send a verification email.
        $this->sendMailService->sendVerificationEmail($user);
    }
}
