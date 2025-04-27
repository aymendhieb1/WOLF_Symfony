<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\HotelRepository;


class HotelDetailsController extends AbstractController
{
    #[Route('/hotel/details/{id}', name: 'app_hotel_details')]
    public function index(int $id, HotelRepository $hotelRepository): Response
    {
        $hotel = $hotelRepository->find($id);

        if (!$hotel) {
            throw $this->createNotFoundException('Hôtel non trouvé.');
        }

        return $this->render('hotel_details/index.html.twig', [
            'hotel' => $hotel,
        ]);
    }
}
