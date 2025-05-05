<?php

namespace App\Service;

use Cravler\PhpOpenweathermapBundle\Service\OpenweathermapService;

class WeatherService
{
    private $weatherService;

    public function __construct(OpenweathermapService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    public function getWeatherForCity(string $city): array
    {
        try {
            $weather = $this->weatherService->getCurrentWeather($city);
            
            return [
                'temperature' => $weather['main']['temp'],
                'description' => $weather['weather'][0]['description'],
                'humidity' => $weather['main']['humidity'],
                'wind_speed' => $weather['wind']['speed'],
                'icon' => $weather['weather'][0]['icon']
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Impossible de récupérer les informations météo pour ' . $city
            ];
        }
    }

    public function getForecastForCity(string $city): array
    {
        try {
            $forecast = $this->weatherService->getForecast($city);
            
            $dailyForecasts = [];
            foreach ($forecast['list'] as $item) {
                $date = new \DateTime('@' . $item['dt']);
                $dailyForecasts[] = [
                    'date' => $date->format('Y-m-d'),
                    'temperature' => $item['main']['temp'],
                    'description' => $item['weather'][0]['description'],
                    'icon' => $item['weather'][0]['icon']
                ];
            }
            
            return $dailyForecasts;
        } catch (\Exception $e) {
            return [
                'error' => 'Impossible de récupérer les prévisions météo pour ' . $city
            ];
        }
    }
} 