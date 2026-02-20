<?php

namespace App\Service;

use App\Dto\ProfileUpdateInputDto;
use App\Entity\User;
use App\Exception\ProfileEmailAlreadyUsedException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProfileService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
    ) {
    }

    public function createUpdateInputDto(User $user): ProfileUpdateInputDto
    {
        return new ProfileUpdateInputDto(
            email: $user->getEmail() ?? '',
            firstName: $user->getFirstName() ?? '',
            lastName: $user->getLastName() ?? '',
            phoneNumber: $user->getPhoneNumber() ?? '',
        );
    }

    /**
     * @return array{
     *     fullName: string,
     *     avatarInitials: string,
     *     membershipLabel: string,
     *     isVerified: bool,
     *     email: string,
     *     phoneNumber: string,
     *     createdAt: \DateTimeImmutable|null,
     *     updatedAt: \DateTimeImmutable|null,
     *     notifications: list<array{label: string, enabled: bool}>,
     *     payment: array{label: string, details: string, buttonLabel: string},
     *     securityActions: list<string>
     * }
     */
    public function getProfileViewData(User $user): array
    {
        return [
            'fullName' => trim(sprintf('%s %s', $user->getFirstName(), $user->getLastName())),
            'avatarInitials' => $this->buildInitials($user),
            'membershipLabel' => $this->membershipLabel($user),
            'isVerified' => (bool) $user->isVerified(),
            'email' => $user->getEmail() ?? '',
            'phoneNumber' => $user->getPhoneNumber() ?? 'Non renseigné',
            'createdAt' => $user->getCreatedAt(),
            'updatedAt' => $user->getUpdatedAt(),
            'notifications' => [
                ['label' => 'Nouvelles enchères', 'enabled' => true],
                ['label' => "Rappels d'encheres", 'enabled' => true],
                ['label' => 'Résultats des ventes', 'enabled' => false],
                ['label' => 'Newsletter', 'enabled' => true],
            ],
            'payment' => [
                'label' => 'Carte enregistrée',
                'details' => '**** **** **** 4242',
                'buttonLabel' => 'Gérer les moyens de paiement',
            ],
            'securityActions' => [
                'Changer mon mot de passe',
                "Configurer l'authentification a deux facteurs",
                'Supprimer mon compte',
            ],
        ];
    }

    /**
     * @throws ProfileEmailAlreadyUsedException
     */
    public function updateProfile(User $user, ProfileUpdateInputDto $dto): void
    {
        $normalizedEmail = strtolower(trim($dto->email));
        $existingUser = $this->userRepository->findOneBy(['email' => $normalizedEmail]);

        if ($existingUser instanceof User && $existingUser->getId() !== $user->getId()) {
            throw new ProfileEmailAlreadyUsedException();
        }

        $user->setFirstName(trim($dto->firstName));
        $user->setLastName(trim($dto->lastName));
        $user->setEmail($normalizedEmail);
        $user->setPhoneNumber(trim($dto->phoneNumber));
        $user->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }

    private function buildInitials(User $user): string
    {
        $first = $user->getFirstName() ?? '';
        $last = $user->getLastName() ?? '';

        $initials = strtoupper(substr($first, 0, 1).substr($last, 0, 1));

        return '' === $initials ? 'U' : $initials;
    }

    private function membershipLabel(User $user): string
    {
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return 'Administrateur';
        }
        if (in_array('ROLE_VENDOR', $roles, true)) {
            return 'Vendeur';
        }

        return 'Membre';
    }
}
