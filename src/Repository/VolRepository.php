<?php
namespace App\Repository;

use App\Entity\Vol;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vol::class);
    }

    public function findFlightsByCriteria(array $criteria)
    {
        $qb = $this->createQueryBuilder('v');

        if (!empty($criteria['depart'])) {
            $qb->andWhere('v.depart LIKE :depart')
                ->setParameter('depart', '%'.$criteria['depart'].'%');
        }

        if (!empty($criteria['destination'])) {
            $qb->andWhere('v.destination LIKE :destination')
                ->setParameter('destination', '%'.$criteria['destination'].'%');
        }

        return $qb->getQuery()->getResult();
    }
    #[Route('/admin/vols', name: 'app_back_vol_index')]
    public function index(VolRepository $volRepository): Response
    {
        $vols = $volRepository->findAll();
        $averages = $volRepository->getAverages();

        return $this->render('back_vol/TableVol.html.twig', [
            'vols' => $vols,
            'averages' => $averages,
        ]);
    }
}