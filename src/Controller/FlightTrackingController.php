<?php

// src/Controller/FlightTrackingController.php
namespace App\Controller;

use App\Service\FlightTrackingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class FlightTrackingController extends AbstractController
{
    private FlightTrackingService $openSkyService;

    // Inject the FlightTrackingService into the controller
    public function __construct(FlightTrackingService $openSkyService)
    {
        $this->openSkyService = $openSkyService;
    }

    // Route to show live flight tracking data
    #[Route('/flights', name: 'app_flight_tracking')]
    public function index()
    {
        // Fetch the live flight data from the OpenSky API
        $flightData = $this->openSkyService->getFlightData();

        // Pass the flight data to the Twig template for rendering
        return $this->render('flight_tracking/index.html.twig', [
            'flights' => $flightData['states'],
        ]);
    }
}
