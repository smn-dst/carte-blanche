<?php

namespace App\Repository;

use App\Entity\Favorite;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favorite>
 */
class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    /**
     * @param string $sort 'date' (proche), 'price_asc', 'price_desc'
     *
     * @return list<Favorite>
     */
    public function findByUser(User $user, string $sort = 'date'): array
    {
        $qb = $this->createQueryBuilder('f')
            ->innerJoin('f.restaurant', 'r')->addSelect('r')
            ->where('f.user = :user')
            ->setParameter('user', $user);

        match ($sort) {
            'price_asc' => $qb->orderBy('r.askingPrice', 'ASC'),
            'price_desc' => $qb->orderBy('r.askingPrice', 'DESC'),
            default => $qb->orderBy('r.auctionDate', 'ASC'),
        };

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<int>
     */
    public function findRestaurantIdsByUser(User $user): array
    {
        $result = $this->createQueryBuilder('f')
            ->select('r.id')
            ->innerJoin('f.restaurant', 'r')
            ->where('f.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleColumnResult();

        return array_values(array_map('intval', $result));
    }
}
