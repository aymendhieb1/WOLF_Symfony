<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class WeatherServiceVol
{
    private $httpClient;
    private $apiKey;
    private $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $_ENV['OPENWEATHERMAP_API_KEY'] ?? null;
        
        if (!$this->apiKey) {
            $this->logger->error('OpenWeatherMap API key is not configured');
        }
    }

    public function getWeatherForCity(string $city, ?array $coordinates = null): array
    {
        if (!$this->apiKey) {
            return ['error' => 'API key not configured'];
        }

        try {
            $url = 'https://api.openweathermap.org/data/2.5/weather';
            $params = [
                'appid' => $this->apiKey,
                'units' => 'metric',
                'lang' => 'fr'
            ];

            // If coordinates are provided, use them; otherwise use city name
            if ($coordinates) {
                $params['lat'] = $coordinates['lat'];
                $params['lon'] = $coordinates['lon'];
            } else {
                $params['q'] = $city;
            }

            $response = $this->httpClient->request('GET', $url, [
                'query' => $params
            ]);

            if ($response->getStatusCode() !== 200) {
                return ['error' => 'Erreur API: ' . $response->getContent(false)];
            }

            $data = $response->toArray();
            return [
                'temperature' => round($data['main']['temp']),
                'feels_like' => round($data['main']['feels_like']),
                'description' => $data['weather'][0]['description'],
                'humidity' => $data['main']['humidity'],
                'wind_speed' => $data['wind']['speed'],
                'icon' => $data['weather'][0]['icon'],
                'city' => $data['name'],
                'sunrise' => date('H:i', $data['sys']['sunrise']),
                'sunset' => date('H:i', $data['sys']['sunset']),
                'pressure' => $data['main']['pressure'],
                'clouds' => $data['clouds']['all']
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error fetching weather for ' . $city . ': ' . $e->getMessage());
            return ['error' => 'Impossible de récupérer les informations météo pour ' . $city . ': ' . $e->getMessage()];
        }
    }

    public function getForecastForCity(string $city, ?array $coordinates = null): array
    {
        if (!$this->apiKey) {
            return ['error' => 'API key not configured'];
        }

        try {
            $url = 'https://api.openweathermap.org/data/2.5/forecast';
            $params = [
                'appid' => $this->apiKey,
                'units' => 'metric',
                'lang' => 'fr',
                'cnt' => 40 // Get 5 days of data (8 readings per day)
            ];

            // If coordinates are provided, use them; otherwise use city name
            if ($coordinates) {
                $params['lat'] = $coordinates['lat'];
                $params['lon'] = $coordinates['lon'];
            } else {
                $params['q'] = $city;
            }

            $response = $this->httpClient->request('GET', $url, [
                'query' => $params
            ]);

            if ($response->getStatusCode() !== 200) {
                return ['error' => 'Erreur API: ' . $response->getContent(false)];
            }

            $data = $response->toArray();
            $forecasts = [];
            $processedDates = [];

            // Get one forecast per day at noon
            foreach ($data['list'] as $item) {
                $date = date('Y-m-d', $item['dt']);
                $hour = date('H', $item['dt']);
                
                // Only process one forecast per day, preferably at noon
                if (!in_array($date, $processedDates) && ($hour == '12' || count($processedDates) < 5)) {
                    $processedDates[] = $date;
                    $forecasts[] = [
                        'date' => date('d/m', $item['dt']),
                        'day' => $this->getFrenchDayName(date('D', $item['dt'])),
                        'temperature' => round($item['main']['temp']),
                        'description' => $item['weather'][0]['description'],
                        'icon' => $item['weather'][0]['icon']
                    ];
                }
            }

            return array_slice($forecasts, 0, 5);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching forecast for ' . $city . ': ' . $e->getMessage());
            return ['error' => 'Impossible de récupérer les prévisions météo pour ' . $city . ': ' . $e->getMessage()];
        }
    }

    private function getFrenchDayName(string $englishDay): string
    {
        $days = [
            'Mon' => 'Lun',
            'Tue' => 'Mar',
            'Wed' => 'Mer',
            'Thu' => 'Jeu',
            'Fri' => 'Ven',
            'Sat' => 'Sam',
            'Sun' => 'Dim'
        ];
        return $days[$englishDay] ?? $englishDay;
    }
} 