<?php

namespace App\Repository;

use App\Entity\ReservationActivite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReservationActivite>
 *
 * @method ReservationActivite|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReservationActivite|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReservationActivite[]    findAll()
 * @method ReservationActivite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationActiviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReservationActivite::class);
    }

    public function save(ReservationActivite $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ReservationActivite $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUserAndSession(int $userId, int $sessionId): ?ReservationActivite
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :userId')
            ->andWhere('r.session = :sessionId')
            ->setParameter('userId', $userId)
            ->setParameter('sessionId', $sessionId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findUserReservations(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.dateRes', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllWithDetails(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.session', 's')
            ->leftJoin('s.activite', 'a')
            ->addSelect('u')
            ->addSelect('s')
            ->addSelect('a')
            ->orderBy('r.dateRes', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 
