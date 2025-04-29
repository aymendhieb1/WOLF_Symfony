<?php

namespace App\Repository;

use App\Entity\Rating;
use App\Entity\User;
use App\Entity\Hotel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rating>
 */
class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    public function canUserRateHotel(User $user, Hotel $hotel): bool
    {
        // Check if user has already rated this hotel
        $existingRating = $this->findUserRatingForHotel($user, $hotel);
        if ($existingRating) {
            return false;
        }

        // Check if user has a reservation for this hotel
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(r)')
           ->from('App\Entity\ReservationChambre', 'r')
           ->join('r.chambre', 'c')
           ->where('c.hotel = :hotel')
           ->andWhere('r.user = :user')
           ->setParameter('hotel', $hotel)
           ->setParameter('user', $user);

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function findUserRatingForHotel(User $user, Hotel $hotel): ?Rating
    {
        return $this->findOneBy([
            'user' => $user,
            'hotel' => $hotel
        ]);
    }

    public function getAverageRating(Hotel $hotel): float
    {
        try {
            $result = $this->createQueryBuilder('r')
                ->select('COALESCE(AVG(r.stars), 0) as avgRating')
                ->where('r.hotel = :hotel')
                ->setParameter('hotel', $hotel)
                ->getQuery()
                ->getSingleScalarResult();

            return round((float) $result, 1);
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    public function getRatingStats(Hotel $hotel): array
    {
        try {
            $qb = $this->createQueryBuilder('r')
                ->select('COALESCE(AVG(r.stars), 0) as avgRating, COUNT(r.id) as totalRatings')
                ->where('r.hotel = :hotel')
                ->setParameter('hotel', $hotel);

            $result = $qb->getQuery()->getSingleResult();

            return [
                'average' => round((float) $result['avgRating'], 1),
                'total' => (int) $result['totalRatings']
            ];
        } catch (\Exception $e) {
            return [
                'average' => 0.0,
                'total' => 0
            ];
        }
    }
} 