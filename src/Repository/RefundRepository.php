<?php

namespace App\Repository;

use App\Entity\Refund;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Refund>
 */
class RefundRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Refund::class);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('rf')
            ->select('COUNT(rf.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPendingForAdmin(): int
    {
        return (int) $this->createQueryBuilder('rf')
            ->select('COUNT(rf.id)')
            ->where('rf.status = :status')
            ->setParameter('status', \App\Enum\StatusRefundEnum::EN_ATTENTE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Demandes en attente, les plus anciennes en premier (FIFO).
     * Jointures eager pour éviter les N+1 en Twig.
     *
     * @return Refund[]
     */
    public function findPendingForAdmin(): array
    {
        return $this->createQueryBuilder('rf')
            ->leftJoin('rf.order', 'o')->addSelect('o')
            ->leftJoin('o.buyer', 'u')->addSelect('u')
            ->leftJoin('o.tickets', 't')->addSelect('t')
            ->leftJoin('t.restaurant', 'r')->addSelect('r')
            ->where('rf.status = :status')
            ->setParameter('status', \App\Enum\StatusRefundEnum::EN_ATTENTE)
            ->orderBy('rf.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Demandes déjà traitées (TRAITE ou REFUSE), les 100 plus récentes.
     *
     * @return Refund[]
     */
    public function findProcessedForAdmin(): array
    {
        return $this->createQueryBuilder('rf')
            ->leftJoin('rf.order', 'o')->addSelect('o')
            ->leftJoin('o.buyer', 'u')->addSelect('u')
            ->leftJoin('rf.processedBy', 'admin')->addSelect('admin')
            ->where('rf.status IN (:statuses)')
            ->setParameter('statuses', [
                \App\Enum\StatusRefundEnum::TRAITE,
                \App\Enum\StatusRefundEnum::REFUSE,
            ])
            ->orderBy('rf.createdAt', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }
}
