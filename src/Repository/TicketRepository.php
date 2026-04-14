<?php

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function findByUser(User $user, ?string $status = null): array
{
    $qb = $this->createQueryBuilder('t')
        ->innerJoin('t.order', 'o')
        ->where('o.buyer = :user')
        ->setParameter('user', $user)
        ->orderBy('t.createdAt', 'DESC');

    if ($status !== 'all') {
        $qb->andWhere('t.status = :status')
           ->setParameter('status', $status);
    }

    return $qb->getQuery()->getResult();
}
}
