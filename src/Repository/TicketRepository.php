<?php

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\StatusTicketEnum;
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

    /**
     * @return Ticket[]
     */
    public function findByUser(User $user, ?StatusTicketEnum $status = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->join('t.order', 'o')
            ->join('t.restaurant', 'r')
            ->where('o.buyer = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC');

        if (null !== $status) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }
}
