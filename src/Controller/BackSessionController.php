<?php

namespace App\Controller;

use App\Repository\SessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class BackSessionController extends AbstractController
{
    #[Route('/back/session', name: 'app_back_session')]
    public function index(SessionRepository $sessionRepository): Response
    {
        return $this->render('back_session/TableSession.html.twig', [
            'sessions' => $sessionRepository->findAll(),
        ]);
    }

    #[Route('/back/session/search', name: 'app_back_session_search', methods: ['GET'])]
    public function search(Request $request, SessionRepository $sessionRepository): Response
    {
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', '');

        $qb = $sessionRepository->createQueryBuilder('s')
            ->leftJoin('s.activite', 'a');

        // Apply search filter if search term is provided
        if (!empty($search)) {
            $qb->where('a.nom LIKE :search OR s.date LIKE :search OR s.heure LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Apply sorting
        switch ($sort) {
            case 'activity_asc':
                $qb->orderBy('a.nom', 'ASC');
                break;
            case 'activity_desc':
                $qb->orderBy('a.nom', 'DESC');
                break;
            case 'date_asc':
                $qb->orderBy('s.date', 'ASC');
                break;
            case 'date_desc':
                $qb->orderBy('s.date', 'DESC');
                break;
            case 'time_asc':
                $qb->orderBy('s.heure', 'ASC');
                break;
            case 'time_desc':
                $qb->orderBy('s.heure', 'DESC');
                break;
            case 'capacity_asc':
                $qb->orderBy('s.capacite', 'ASC');
                break;
            case 'capacity_desc':
                $qb->orderBy('s.capacite', 'DESC');
                break;
            default:
                $qb->orderBy('s.date', 'ASC');
        }

        $sessions = $qb->getQuery()->getResult();

        return $this->render('back_session/_sessions_table.html.twig', [
            'sessions' => $sessions,
        ]);
    }
}
