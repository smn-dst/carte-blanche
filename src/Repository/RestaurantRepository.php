<?php

namespace App\Repository;

use App\Entity\Restaurant;
use App\Entity\User;
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
            // Requête d’IDs seule : LIMIT/OFFSET dans une sous-requête IN() est souvent ignoré par le SQL généré → pages dupliquées.
            $idsQuery = $this->createIdsQueryBuilder($search, $categoryId, $sortBy, $minPrice, $maxPrice, $priceSort, $revenueSort, 'sub', 'subCat')
                ->getQuery()
                ->setMaxResults($limit)
                ->setFirstResult($offset);

            $ids = $idsQuery->getSingleColumnResult();
            if ([] === $ids) {
                return [];
            }

            $qb = $this->createQueryBuilder('r')
                ->leftJoin('r.categories', 'c')->addSelect('c')
                ->leftJoin('r.images', 'i')->addSelect('i')
                ->where('r.id IN (:ids)')
                ->setParameter('ids', $ids);

            $restaurants = $qb->getQuery()->getResult();
            usort($restaurants, static fn (Restaurant $a, Restaurant $b): int => array_search($a->getId(), $ids, true) <=> array_search($b->getId(), $ids, true));

            return $restaurants;
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

        $conditions[] = $rootAlias.'.status IN (:auctionStatuses)';
        $qb->setParameter('auctionStatuses', self::statusesVisibleOnEncheresList());

        $qb->where(implode(' AND ', $conditions));
    }

    /**
     * Enchères encore « actives » : à venir, publiées ou en cours — pas terminées ni vendues.
     *
     * @return list<StatusRestaurantEnum>
     */
    private static function statusesVisibleOnEncheresList(): array
    {
        return [
            StatusRestaurantEnum::PUBLIE,
            StatusRestaurantEnum::PROGRAMME,
            StatusRestaurantEnum::EN_COURS,
        ];
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
     * Restaurants appartenant à un propriétaire donné.
     *
     * @return Restaurant[]
     */
    public function findByOwner(User $owner): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.categories', 'c')->addSelect('c')
            ->leftJoin('r.images', 'i')->addSelect('i')
            ->where('r.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
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

    /**
     * Nombre de restaurants en attente de validation admin (statut EN_MODERATION).
     */
    public function countPendingValidation(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :status')
            ->setParameter('status', StatusRestaurantEnum::EN_MODERATION)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Restaurants en attente de validation admin (statut EN_MODERATION).
     * Les plus anciens en premier (FIFO).
     *
     * @return list<Restaurant>
     */
    public function findPendingValidation(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.owner', 'u')->addSelect('u')
            ->leftJoin('r.categories', 'c')->addSelect('c')
            ->leftJoin('r.images', 'i')->addSelect('i')
            ->where('r.status = :status')
            ->setParameter('status', StatusRestaurantEnum::EN_MODERATION)
            ->orderBy('r.updatedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Restaurants récemment approuvés ou refusés (30 derniers).
     *
     * @return Restaurant[]
     */
    public function findRecentlyProcessed(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.owner', 'u')->addSelect('u')
            ->leftJoin('r.categories', 'c')->addSelect('c')
            ->leftJoin('r.images', 'i')->addSelect('i')
            ->where('r.status IN (:statuses)')
            ->setParameter('statuses', [
                StatusRestaurantEnum::PUBLIE,
                StatusRestaurantEnum::PROGRAMME,
                StatusRestaurantEnum::BROUILLON,
                StatusRestaurantEnum::ANNULE,
            ])
            ->orderBy('r.updatedAt', 'DESC')
            ->setMaxResults(30)
            ->getQuery()
            ->getResult();
    }
}
