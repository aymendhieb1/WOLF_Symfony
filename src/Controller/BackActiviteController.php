<?php

namespace App\Controller;

use App\Repository\ActiviteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class BackActiviteController extends AbstractController
{
    #[Route('/back/activite', name: 'app_back_activite')]
    public function index(ActiviteRepository $activiteRepository): Response
    {
        $activities = $activiteRepository->findAll();
        
        // Calculate activity type statistics
        $typeStats = [];
        $total = count($activities);
        
        foreach ($activities as $activity) {
            $type = $activity->getType();
            if (!isset($typeStats[$type])) {
                $typeStats[$type] = 0;
            }
            $typeStats[$type]++;
        }
        
        $activityStats = [
            'total' => $total,
            'labels' => array_keys($typeStats),
            'data' => array_values($typeStats)
        ];

        return $this->render('back_activite/TableActivite.html.twig', [
            'activites' => $activities,
            'activityStats' => $activityStats
        ]);
    }

    #[Route('/back/activite/search', name: 'app_back_activite_search', methods: ['GET'])]
    public function search(Request $request, ActiviteRepository $activiteRepository): Response
    {
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', '');

        $qb = $activiteRepository->createQueryBuilder('a');

        // Apply search filter if search term is provided
        if (!empty($search)) {
            $qb->where('a.nom LIKE :search OR a.description LIKE :search OR a.type LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Apply sorting
        switch ($sort) {
            case 'nom_asc':
                $qb->orderBy('a.nom', 'ASC');
                break;
            case 'nom_desc':
                $qb->orderBy('a.nom', 'DESC');
                break;
            case 'prix_asc':
                $qb->orderBy('a.prix', 'ASC');
                break;
            case 'prix_desc':
                $qb->orderBy('a.prix', 'DESC');
                break;
            case 'type_asc':
                $qb->orderBy('a.type', 'ASC');
                break;
            case 'type_desc':
                $qb->orderBy('a.type', 'DESC');
                break;
            default:
                $qb->orderBy('a.nom', 'ASC');
        }

        $activites = $qb->getQuery()->getResult();

        return $this->render('back_activite/_activites_table.html.twig', [
            'activites' => $activites,
        ]);
    }
}
