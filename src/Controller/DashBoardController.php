<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use App\Repository\TodoRepository;
use App\Repository\VehiculeRepository;
use Doctrine\DBAL\Connection;


class DashBoardController extends AbstractController
{
    #[Route('/dash/board', name: 'app_dash_board')]
    public function index(UserRepository $userRepository, TodoRepository $todoRepository, VehiculeRepository $vehicleRepository, Connection $connection): Response
    {
        $sql = "SELECT SUM(montant) as total_revenue FROM paiement";
        $stmt = $connection->executeQuery($sql);
        $totalRevenue = $stmt->fetchOne();
        $vehicles = $vehicleRepository->findAll();

        $cylinderCounts = [];
        foreach ($vehicles as $vehicle) {
            $cylinder = $vehicle->getCylinder();
            if ($cylinder !== null) {
                if (!isset($cylinderCounts[$cylinder])) {
                    $cylinderCounts[$cylinder] = 0;
                }
                $cylinderCounts[$cylinder]++;
            }
        }
        $vehicleCylinderStats = [
            'labels' => array_map(function ($c) {
                return $c . ' Cylinders';
            }, array_keys($cylinderCounts)),
            'data' => array_values($cylinderCounts),
            'colors' => ['#4e73df', '#1cc88a', '#36b9cc', '#ff681a', '#f6c23e']
        ];
        return $this->render('dash_board/index.html.twig', [
            'userCountRole2' => $userRepository->count(['role' => 2]),
            'userCountStatus1' => $userRepository->count(['status' => 1]),
            'userStatusStats' => [
                'labels' => ['Comptes Actifs', 'Comptes Bloqués'],
                'data' => [
                    $userRepository->count(['status' => 0]),
                    $userRepository->count(['status' => 1])
                ],
                'colors' => ['#ff681a', '#132a3e'],
                'borderColors' => ['#132a3e', '#ff681a']
            ],
            'todos' => $todoRepository->findBy([], ['createdAt' => 'DESC']),
            'vehicleCylinderStats' => $vehicleCylinderStats,
            'totalRevenue' => $totalRevenue



        ]);
    }
}

