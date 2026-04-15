<?php

namespace App\Repository;

use App\Entity\Cart;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cart>
 */
class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
    }

    /**
     * Retourne les paniers non vides, non modifiés depuis $abandonedBefore,
     * dont l'email d'abandon n'a pas encore été envoyé.
     *
     * @return Cart[]
     */
    public function findAbandonedCarts(\DateTimeImmutable $abandonedBefore): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.cartItems', 'ci')
            ->innerJoin('c.user', 'u')
            ->where('c.updatedAt < :before')
            ->andWhere('c.abandonedCartEmailSentAt IS NULL')
            ->andWhere('u.notifNewAuctions = true')
            ->setParameter('before', $abandonedBefore)
            ->getQuery()
            ->getResult();
    }
}
