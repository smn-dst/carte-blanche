<?php

namespace App\Repository;

use App\Entity\FaqEmbedding;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FaqEmbedding>
 */
class FaqEmbeddingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FaqEmbedding::class);
    }
}
