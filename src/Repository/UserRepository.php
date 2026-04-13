<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Ajouter cette méthode dans src/Repository/UserRepository.php.
     *
     * Recherche d'utilisateurs pour le panel admin.
     * Filtre par rôle (ex: 'ROLE_ADMIN', 'ROLE_VENDOR', 'ROLE_USER')
     * et/ou par recherche texte sur email, prénom, nom.
     *
     * @return User[]
     */
    public function findForAdmin(?string $role = null, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC');

        if (null !== $search && '' !== $search) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(u.email) LIKE LOWER(:search)',
                    'LOWER(u.firstName) LIKE LOWER(:search)',
                    'LOWER(u.lastName) LIKE LOWER(:search)',
                )
            )->setParameter('search', '%'.$search.'%');
        }

        $users = $qb->getQuery()->getResult();

        // Filtre rôle en PHP (les rôles sont stockés en JSON, pas requêtable facilement en DQL)
        if (null !== $role && '' !== $role) {
            $users = array_values(array_filter(
                $users,
                static fn (User $u): bool => in_array($role, $u->getRoles(), true)
            ));
        }

        return $users;
    }
}
