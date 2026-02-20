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
     * Recherche les restaurants par nom et/ou catégorie avec tri optionnel et filtre de prix.
     *
     * @return Restaurant[]
     */
    public function searchByNameAndCategory(?string $search, ?int $categoryId, ?string $sortBy = null, ?float $minPrice = null, ?float $maxPrice = null, ?string $priceSort = null): array
    {
        $qb = $this->createQueryBuilder('r');
        $conditions = [];

        if ($search) {
            $conditions[] = 'LOWER(r.name) LIKE LOWER(:search)';
            $qb->setParameter('search', '%'.$search.'%');
        }

        if ($categoryId) {
            $qb->innerJoin('r.categories', 'c');
            $conditions[] = 'c.id = :categoryId';
            $qb->setParameter('categoryId', $categoryId);
        }

        if (null !== $minPrice) {
            $conditions[] = 'r.askingPrice >= :minPrice';
            $qb->setParameter('minPrice', $minPrice);
        }

        if (null !== $maxPrice) {
            $conditions[] = 'r.askingPrice <= :maxPrice';
            $qb->setParameter('maxPrice', $maxPrice);
        }

        if (!empty($conditions)) {
            $qb->where(implode(' AND ', $conditions));
        }

        if ('recent' === $sortBy) {
            $qb->orderBy('r.createdAt', 'DESC');
        } elseif ('old' === $sortBy) {
            $qb->orderBy('r.createdAt', 'ASC');
        }

        if ('asc' === $priceSort) {
            $qb->orderBy('r.askingPrice', 'ASC');
        } elseif ('desc' === $priceSort) {
            $qb->orderBy('r.askingPrice', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère le prix minimum et maximum de tous les restaurants.
     *
     * @return array{min: float, max: float}
     */
    public function getPriceRange(): array
    {
        $result = $this->createQueryBuilder('r')
            ->select('MIN(r.askingPrice) as minPrice', 'MAX(r.askingPrice) as maxPrice')
            ->getQuery()
            ->getSingleResult();

        return [
            'min' => (float) ($result['minPrice'] ?? 0),
            'max' => (float) ($result['maxPrice'] ?? 0),
        ];
    }

    /**
     * Restaurants pour la section « Enchères exclusives » de la home.
     * Statuts PUBLIE et PROGRAMME : achetable ou à venir.
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