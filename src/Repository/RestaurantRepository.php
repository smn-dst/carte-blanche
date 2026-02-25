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
     * Restaurants affichables sur la carte (publiés et programmés).
     * Eager load categories + images pour éviter les requêtes N+1.
     *
     * @return Restaurant[]
     */
    public function findForMap(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.categories', 'c')->addSelect('c')
            ->leftJoin('r.images', 'i')->addSelect('i')
            ->where('r.status IN (:statuses)')
            ->setParameter('statuses', [
                StatusRestaurantEnum::PUBLIE,
                StatusRestaurantEnum::PROGRAMME,
            ])
            ->andWhere('r.latitude IS NOT NULL')
            ->andWhere('r.longitude IS NOT NULL')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche les restaurants par nom et/ou catégorie avec tri optionnel et filtre de prix.
     * Avec limit/offset, une seule requête (sous-requête IN) pour éviter N+1 tout en gardant le bon ordre.
     *
     * @return Restaurant[]
     */
    public function searchByNameAndCategory(?string $search, ?int $categoryId, ?string $sortBy = null, ?float $minPrice = null, ?float $maxPrice = null, ?string $priceSort = null, ?string $revenueSort = null, ?int $limit = null, int $offset = 0): array
    {
        if (null !== $limit) {
            $subQb = $this->createIdsQueryBuilder($search, $categoryId, $sortBy, $minPrice, $maxPrice, $priceSort, $revenueSort, 'sub', 'subCat');
            $subQb->setMaxResults($limit)->setFirstResult($offset);

            $qb = $this->createQueryBuilder('r')
                ->leftJoin('r.categories', 'c')->addSelect('c')
                ->leftJoin('r.images', 'i')->addSelect('i')
                ->where('r.id IN ('.$subQb->getDQL().')');
            foreach ($subQb->getParameters() as $param) {
                $qb->setParameter($param->getName(), $param->getValue());
            }
            $this->applySearchOrder($qb, $sortBy, $priceSort, $revenueSort);

            return $qb->getQuery()->getResult();
        }

        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.categories', 'c')->addSelect('c')
            ->leftJoin('r.images', 'i')->addSelect('i');
        $this->applySearchConditions($qb, $search, $categoryId, $minPrice, $maxPrice);
        $this->applySearchOrder($qb, $sortBy, $priceSort, $revenueSort);

        return $qb->getQuery()->getResult();
    }

    /**
     * QueryBuilder pour les IDs (sous-requête ou count), sans jointures inutiles.
     *
     * @param string $rootAlias     Alias de la racine (utiliser 'sub' quand injecté dans une requête qui a déjà 'r')
     * @param string $categoryAlias Alias du join categories (ex. 'subCat' pour éviter conflit avec 'c')
     */
    private function createIdsQueryBuilder(?string $search, ?int $categoryId, ?string $sortBy, ?float $minPrice, ?float $maxPrice, ?string $priceSort, ?string $revenueSort, string $rootAlias = 'r', string $categoryAlias = 'c'): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder($rootAlias)->select($rootAlias.'.id');
        if ($categoryId) {
            $qb->innerJoin($rootAlias.'.categories', $categoryAlias);
        }
        $this->applySearchConditions($qb, $search, $categoryId, $minPrice, $maxPrice, $rootAlias, $categoryAlias);
        $this->applySearchOrder($qb, $sortBy, $priceSort, $revenueSort, $rootAlias);

        return $qb;
    }

    private function applySearchOrder(\Doctrine\ORM\QueryBuilder $qb, ?string $sortBy, ?string $priceSort, ?string $revenueSort = null, string $rootAlias = 'r'): void
    {
        if ('recent' === $sortBy) {
            $qb->orderBy($rootAlias.'.createdAt', 'DESC');
        } elseif ('old' === $sortBy) {
            $qb->orderBy($rootAlias.'.createdAt', 'ASC');
        }
        if ('asc' === $priceSort) {
            $qb->orderBy($rootAlias.'.askingPrice', 'ASC');
        } elseif ('desc' === $priceSort) {
            $qb->orderBy($rootAlias.'.askingPrice', 'DESC');
        }
        if ('asc' === $revenueSort) {
            $qb->orderBy($rootAlias.'.annualRevenue', 'ASC');
        } elseif ('desc' === $revenueSort) {
            $qb->orderBy($rootAlias.'.annualRevenue', 'DESC');
        }
    }

    /**
     * Compte le nombre de restaurants pour les mêmes critères que searchByNameAndCategory.
     */
    public function countSearchByNameAndCategory(?string $search, ?int $categoryId, ?float $minPrice = null, ?float $maxPrice = null): int
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(DISTINCT r.id)');
        if ($categoryId) {
            $qb->innerJoin('r.categories', 'c');
        }
        $this->applySearchConditions($qb, $search, $categoryId, $minPrice, $maxPrice);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Données pour la pagination + fourchette de prix globale (une seule requête).
     *
     * @return array{total: int, minPrice: float, maxPrice: float}
     */
    public function getPaginationAndPriceRange(?string $search, ?int $categoryId, ?float $minPrice = null, ?float $maxPrice = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select(
                'COUNT(DISTINCT r.id) as total',
                '(SELECT MIN(sub1.askingPrice) FROM '.Restaurant::class.' sub1) as minPrice',
                '(SELECT MAX(sub2.askingPrice) FROM '.Restaurant::class.' sub2) as maxPrice'
            );
        if ($categoryId) {
            $qb->innerJoin('r.categories', 'c');
        }
        $this->applySearchConditions($qb, $search, $categoryId, $minPrice, $maxPrice);

        $result = $qb->getQuery()->getSingleResult();

        return [
            'total' => (int) ($result['total'] ?? 0),
            'minPrice' => (float) ($result['minPrice'] ?? 0),
            'maxPrice' => (float) ($result['maxPrice'] ?? 0),
        ];
    }

    private function applySearchConditions(\Doctrine\ORM\QueryBuilder $qb, ?string $search, ?int $categoryId, ?float $minPrice, ?float $maxPrice, string $rootAlias = 'r', string $categoryAlias = 'c'): void
    {
        $conditions = [];

        if ($search) {
            $conditions[] = 'LOWER('.$rootAlias.'.name) LIKE LOWER(:search)';
            $qb->setParameter('search', '%'.$search.'%');
        }

        if ($categoryId) {
            $conditions[] = $categoryAlias.'.id = :categoryId';
            $qb->setParameter('categoryId', $categoryId);
        }

        if (null !== $minPrice) {
            $conditions[] = $rootAlias.'.askingPrice >= :minPrice';
            $qb->setParameter('minPrice', sprintf('%.2f', round($minPrice, 2)));
        }

        if (null !== $maxPrice) {
            $conditions[] = $rootAlias.'.askingPrice <= :maxPrice';
            $qb->setParameter('maxPrice', sprintf('%.2f', round($maxPrice, 2)));
        }

        if (!empty($conditions)) {
            $qb->where(implode(' AND ', $conditions));
        }
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
