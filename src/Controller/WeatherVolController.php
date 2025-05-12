<?php

namespace App\Controller;

use App\Service\WeatherServiceVol;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class WeatherVolController extends AbstractController
{
    private $weatherService;
    private $logger;

    public function __construct(WeatherServiceVol $weatherService, LoggerInterface $logger)
    {
        $this->weatherService = $weatherService;
        $this->logger = $logger;
    }

    #[Route('/api/weather/{city}', name: 'app_weather_vol_get', methods: ['GET'])]
    public function getWeather(string $city, Request $request): JsonResponse
    {
        try {
            $this->logger->info('Fetching weather for city: ' . $city);
            
            // Get coordinates from request if available
            $coordinates = null;
            if ($request->query->has('lat') && $request->query->has('lon')) {
                $coordinates = [
                    'lat' => $request->query->get('lat'),
                    'lon' => $request->query->get('lon')
                ];
                $this->logger->info('Using coordinates: ' . json_encode($coordinates));
            }
            
            $weather = $this->weatherService->getWeatherForCity($city, $coordinates);
            if (isset($weather['error'])) {
                $this->logger->error('Weather service error: ' . $weather['error']);
                return $this->json([
                    'error' => $weather['error'],
                    'details' => 'Error fetching current weather'
                ], Response::HTTP_BAD_REQUEST);
            }

            $forecast = $this->weatherService->getForecastForCity($city, $coordinates);
            $this->logger->info('Forecast data: ' . json_encode($forecast));
            
            if (isset($forecast['error'])) {
                $this->logger->error('Forecast service error: ' . $forecast['error']);
                return $this->json([
                    'error' => $forecast['error'],
                    'details' => 'Error fetching forecast'
                ], Response::HTTP_BAD_REQUEST);
            }

            return $this->json([
                'weather' => $weather,
                'forecast' => $forecast
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Exception in weather controller: ' . $e->getMessage());
            return $this->json([
                'error' => 'Une erreur est survenue lors de la récupération des données météo',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 