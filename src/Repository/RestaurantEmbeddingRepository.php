<?php

namespace App\Repository;

use App\Entity\RestaurantEmbedding;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RestaurantEmbedding>
 */
class RestaurantEmbeddingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RestaurantEmbedding::class);
    }

    /**
     * Retourne tous les embeddings de restaurants dont le vecteur est non-vide,
     * avec le restaurant associé chargé en eager loading (évite les N+1).
     *
     * On passe par DBAL pour filtrer avec json_array_length() (PostgreSQL),
     * puis on charge les entités via DQL pour bénéficier de l'hydratation Doctrine.
     *
     * @return RestaurantEmbedding[]
     */
    public function findAllWithNonEmptyEmbedding(): array
    {
        $ids = $this->getEntityManager()->getConnection()
            ->executeQuery('SELECT id FROM restaurant_embedding WHERE json_array_length(embedding) > 0')
            ->fetchFirstColumn();

        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('re')
            ->select('re', 'r')
            ->join('re.restaurant', 'r')
            ->where('re.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
}
