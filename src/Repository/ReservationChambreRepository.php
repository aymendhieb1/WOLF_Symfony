<?php

namespace App\Repository;

use App\Entity\ReservationChambre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReservationChambre>
 *
 * @method ReservationChambre|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReservationChambre|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReservationChambre[]    findAll()
 * @method ReservationChambre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationChambreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReservationChambre::class);
    }

//    /**
//     * @return ReservationChambre[] Returns an array of ReservationChambre objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ReservationChambre
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
