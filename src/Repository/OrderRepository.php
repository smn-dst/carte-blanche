<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Statistiques globales pour le dashboard admin.
     * Somme du CA et nombre de commandes sur tous les statuts aboutis.
     *
     * @return array{totalRevenue: float, orderCount: int}
     */
    public function getDashboardStats(): array
    {
        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.totalAmount) as totalRevenue', 'COUNT(o.id) as orderCount')
            ->where('o.status IN (:statuses)')
            ->setParameter('statuses', [
                \App\Enum\StatusOrderEnum::PAYEE,
                \App\Enum\StatusOrderEnum::REMBOURSEMENT_PARTIEL,
                \App\Enum\StatusOrderEnum::REMBOURSEE,
            ])
            ->getQuery()
            ->getSingleResult();

        return [
            'totalRevenue' => (float) ($result['totalRevenue'] ?? 0),
            'orderCount' => (int) ($result['orderCount'] ?? 0),
        ];
    }

    /**
     * Ajouter cette méthode dans src/Repository/OrderRepository.php
     * (avant le dernier }).
     *
     * Toutes les commandes abouties : PAYEE, REMBOURSEMENT_PARTIEL, REMBOURSEE.
     * Jointures eager pour éviter les N+1 en Twig.
     *
     * @return Order[]
     */
    public function findCompletedOrders(): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.buyer', 'u')->addSelect('u')
            ->leftJoin('o.tickets', 't')->addSelect('t')
            ->leftJoin('t.restaurant', 'r')->addSelect('r')
            ->where('o.status IN (:statuses)')
            ->setParameter('statuses', [
                \App\Enum\StatusOrderEnum::PAYEE,
                \App\Enum\StatusOrderEnum::REMBOURSEMENT_PARTIEL,
                \App\Enum\StatusOrderEnum::REMBOURSEE,
            ])
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
