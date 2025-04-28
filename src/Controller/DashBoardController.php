<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use App\Repository\TodoRepository; // <-- ajouter ça


class DashBoardController extends AbstractController
{
    #[Route('/dash/board', name: 'app_dash_board')]
    public function index(UserRepository $userRepository, TodoRepository $todoRepository): Response // <-- injecter TodoRepository
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
                'colors' => [' #ff681a', '#132a3e'],
                'borderColors' => ['#132a3e', '#ff681a']
            ],
            'todos' => $todoRepository->findBy([], ['createdAt' => 'DESC']) // <-- ajouter la récupération des todos
        ]);
    }
}
