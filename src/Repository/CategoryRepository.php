<?php

namespace App\Repository;

use App\Entity\Category;
use App\Enum\StatusRestaurantEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Catégories les plus représentées parmi les restaurants visibles sur la plateforme (publiés / programmés),
     * avec le nombre de restaurants par catégorie.
     *
     * @return list<array{category: Category, count: int}>
     */
    public function findPopularForHome(int $limit = 8): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.id AS categoryId')
            ->addSelect('COUNT(r.id) AS restaurantCount')
            ->innerJoin('c.restaurants', 'r')
            ->where('r.status IN (:statuses)')
            ->setParameter('statuses', [
                StatusRestaurantEnum::PUBLIE,
                StatusRestaurantEnum::PROGRAMME,
            ])
            ->groupBy('c.id')
            ->orderBy('COUNT(r.id)', 'DESC')
            ->addOrderBy('c.name', 'ASC')
            ->setMaxResults($limit);

        $rows = $qb->getQuery()->getScalarResult();
        if ([] === $rows) {
            return [];
        }

        $ids = [];
        $countById = [];
        foreach ($rows as $row) {
            $id = (int) ($row['categoryId'] ?? $row['categoryid'] ?? 0);
            $cnt = (int) ($row['restaurantCount'] ?? $row['restaurantcount'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
                $countById[$id] = $cnt;
            }
        }

        if ([] === $ids) {
            return [];
        }

        /** @var list<Category> $categories */
        $categories = $this->createQueryBuilder('c')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $byId = [];
        foreach ($categories as $category) {
            $byId[$category->getId()] = $category;
        }

        $out = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $out[] = [
                    'category' => $byId[$id],
                    'count' => $countById[$id],
                ];
            }
        }

        return $out;
    }
}
