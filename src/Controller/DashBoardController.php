<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;

class DashBoardController extends AbstractController
{
    #[Route('/dash/board', name: 'app_dash_board')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('dash_board/index.html.twig', [
            'userCountRole2' => $userRepository->count(['role' => 2]),
            'userCountStatus1' => $userRepository->count(['status' => 1]),
            'userStatusStats' => [
                'labels' => ['Comptes Actifs', 'Comptes Bloqués'],
                'data' => [
                    $userRepository->count(['status' => 0]), // Actifs
                    $userRepository->count(['status' => 1])  // Bloqués
                ],
                'colors' => ['#4CAF50', '#F44336'],
                'borderColors' => ['#388E3C', '#D32F2F']
            ]
        ]);
    }
}
