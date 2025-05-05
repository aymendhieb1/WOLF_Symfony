<?php

namespace App\Repository;

use App\Entity\Checkout;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CheckoutRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Checkout::class);
    }

    // Add custom query methods if needed

    // Example: Find all checkouts
    public function findAllCheckouts(): array
    {
        return $this->findBy([], ['reservationDate' => 'ASC']);
    }

    // Example: Find a specific checkout by its ID
    public function findCheckoutById(int $id): ?Checkout
    {
        return $this->find($id);
    }

    // Example: Find checkouts by status (e.g. "Confirmee")
    public function findCheckoutsByStatus(string $status): array
    {
        return $this->findBy(['reservationStatus' => $status], ['reservationDate' => 'ASC']);
    }

    // Example: Find checkouts within a specific date range
    public function findCheckoutsByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.reservationDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('c.reservationDate', 'ASC')
            ->getQuery();

        return $qb->getResult();
    }
}
