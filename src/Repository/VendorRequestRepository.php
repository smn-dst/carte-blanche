<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\VendorRequest;
use App\Enum\StatusVendorRequestEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VendorRequest>
 */
class VendorRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VendorRequest::class);
    }

    public function findPendingByUser(User $user): ?VendorRequest
    {
        return $this->findOneBy([
            'user' => $user,
            'status' => StatusVendorRequestEnum::EN_ATTENTE,
        ]);
    }

    public function hasPendingRequest(User $user): bool
    {
        return null !== $this->findPendingByUser($user);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('vr')
            ->select('COUNT(vr.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPendingForAdmin(): int
    {
        return (int) $this->createQueryBuilder('vr')
            ->select('COUNT(vr.id)')
            ->where('vr.status = :status')
            ->setParameter('status', StatusVendorRequestEnum::EN_ATTENTE)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
