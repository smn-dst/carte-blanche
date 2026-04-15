<?php

namespace App\Repository;

use App\Entity\Ticket;
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
     * Retourne les tickets valides dont l'événement se déroule entre $from et $to,
     * dont le rappel n'a pas encore été envoyé.
     *
     * @return Ticket[]
     */
    public function findTicketsForUpcomingEvents(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
    ): array {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.restaurant', 'r')
            ->innerJoin('t.order', 'o')
            ->innerJoin('o.buyer', 'u')
            ->where('r.auctionDate BETWEEN :from AND :to')
            ->andWhere('t.status = :status')
            ->andWhere('t.reminderEmailSentAt IS NULL')
            ->andWhere('u.notifReminders = true')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('status', StatusTicketEnum::VALIDE)
            ->getQuery()
            ->getResult();
    }
}
