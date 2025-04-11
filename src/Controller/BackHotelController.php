<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class BackHotelController extends AbstractController
{
    #[Route('/back/hotel', name: 'app_back_hotel')]
    public function index(): Response
    {
        return $this->render('back_hotel/TableHotel.html.twig', [
            'controller_name' => 'BackHotelController',
        ]);
    }
}
