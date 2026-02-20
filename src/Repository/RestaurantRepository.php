<?php

namespace App\Repository;

use App\Entity\Restaurant;
use App\Enum\StatusRestaurantEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Restaurant>
 */
class RestaurantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Restaurant::class);
    }

    /**
     * Restaurants pour la section « Enchères exclusives » de la home.
     * Statuts PUBLIE et PROGRAMME : achetable ou à venir.
     * (Condition propre à cette section pour l'instant.).
     *
     * @return Restaurant[]
     */
    public function findFeaturedForHome(int $maxResults = 5): array
    {
        $ids = $this->createQueryBuilder('r')
            ->select('r.id')
            ->where('r.status IN (:statuses)')
            ->setParameter('statuses', [
                StatusRestaurantEnum::PUBLIE,
                StatusRestaurantEnum::PROGRAMME,
            ])
            ->orderBy('r.updatedAt', 'DESC')
            ->addOrderBy('r.createdAt', 'DESC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getSingleColumnResult();

        if ([] === $ids) {
            return [];
        }

        $restaurants = $this->createQueryBuilder('r')
            ->leftJoin('r.categories', 'c')->addSelect('c')
            ->leftJoin('r.images', 'i')->addSelect('i')
            ->where('r.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        usort($restaurants, fn (Restaurant $a, Restaurant $b): int => array_search($a->getId(), $ids, true) <=> array_search($b->getId(), $ids, true));

        return $restaurants;
    }
}
