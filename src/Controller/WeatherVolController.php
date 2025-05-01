<?php

namespace App\Controller;

use App\Service\WeatherServiceVol;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    public function getWeather(string $city): JsonResponse
    {
        try {
            $this->logger->info('Fetching weather for city: ' . $city);
            
            $weather = $this->weatherService->getWeatherForCity($city);
            if (isset($weather['error'])) {
                $this->logger->error('Weather service error: ' . $weather['error']);
                return $this->json([
                    'error' => $weather['error'],
                    'details' => 'Error fetching current weather'
                ], Response::HTTP_BAD_REQUEST);
            }

            $forecast = $this->weatherService->getForecastForCity($city);
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