<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class WeatherServiceVol
{
    private $httpClient;
    private $apiKey;
    private $logger;
    private $cityCountryMap = [
        'Tunis' => 'Tunis,TN',
        'Italy' => 'Rome,IT',
        'Paris' => 'Paris,FR',
        'London' => 'London,GB',
        'New York' => 'New York,US',
        'Tokyo' => 'Tokyo,JP',
        'Dubai' => 'Dubai,AE',
        'Cairo' => 'Cairo,EG',
        'Madrid' => 'Madrid,ES',
        'Berlin' => 'Berlin,DE',
        'Amsterdam' => 'Amsterdam,NL',
        'Rome' => 'Rome,IT',
        'Barcelona' => 'Barcelona,ES',
        'Istanbul' => 'Istanbul,TR',
        'Moscow' => 'Moscow,RU',
        'Beijing' => 'Beijing,CN',
        'Sydney' => 'Sydney,AU',
        'Singapore' => 'Singapore,SG',
        'Hong Kong' => 'Hong Kong,HK',
        'Bangkok' => 'Bangkok,TH'
    ];

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $_ENV['OPENWEATHERMAP_API_KEY'] ?? null;
        
        // Debug log to check API key
        $this->logger->debug('API Key first 4 chars: ' . substr($this->apiKey, 0, 4) . '...');
        
        if (!$this->apiKey) {
            $this->logger->error('OpenWeatherMap API key is not configured');
        } else {
            $this->logger->info('OpenWeatherMap API key is configured');
        }
    }

    private function formatCityName(string $city): string
    {
        // If we have a mapping for this city, use it
        if (isset($this->cityCountryMap[$city])) {
            return $this->cityCountryMap[$city];
        }

        // If the city name already contains a comma, assume it's already formatted
        if (strpos($city, ',') !== false) {
            return $city;
        }

        // Default to Tunisia for cities without a specific mapping
        return $city . ',TN';
    }

    public function getWeatherForCity(string $city): array
    {
        if (!$this->apiKey) {
            $this->logger->error('API key not configured');
            return ['error' => 'API key not configured'];
        }

        try {
            $formattedCity = $this->formatCityName($city);
            $this->logger->info('Fetching current weather for city: ' . $formattedCity);
            
            $url = 'https://api.openweathermap.org/data/2.5/weather';
            $params = [
                'q' => $formattedCity,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'lang' => 'fr'
            ];

            // Log the full request URL (with masked API key)
            $debugParams = $params;
            $debugParams['appid'] = substr($this->apiKey, 0, 4) . '...';
            $this->logger->debug('Request URL: ' . $url . '?' . http_build_query($debugParams));
            
            $response = $this->httpClient->request('GET', $url, [
                'query' => $params
            ]);

            $statusCode = $response->getStatusCode();
            $this->logger->info('Response status code: ' . $statusCode);

            // Get the raw response content for debugging
            $content = $response->getContent(false);
            $this->logger->debug('Raw API Response: ' . $content);

            if ($statusCode !== 200) {
                $this->logger->error('API Error Response: ' . $content);
                return ['error' => 'Erreur API: ' . $content];
            }

            $data = $response->toArray();
            $this->logger->info('Successfully fetched weather data for: ' . $formattedCity);

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

    public function getForecastForCity(string $city): array
    {
        if (!$this->apiKey) {
            $this->logger->error('API key not configured');
            return ['error' => 'API key not configured'];
        }

        try {
            $formattedCity = $this->formatCityName($city);
            $this->logger->info('Fetching forecast for city: ' . $formattedCity);
            
            $url = 'https://api.openweathermap.org/data/2.5/forecast';
            $params = [
                'q' => $formattedCity,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'lang' => 'fr'
            ];

            // Log the full request URL (with masked API key)
            $debugParams = $params;
            $debugParams['appid'] = substr($this->apiKey, 0, 4) . '...';
            $this->logger->debug('Request URL: ' . $url . '?' . http_build_query($debugParams));
            
            $response = $this->httpClient->request('GET', $url, [
                'query' => $params
            ]);

            $statusCode = $response->getStatusCode();
            $this->logger->info('Response status code: ' . $statusCode);

            // Get the raw response content for debugging
            $content = $response->getContent(false);
            $this->logger->debug('Raw API Response: ' . $content);

            if ($statusCode !== 200) {
                $this->logger->error('API Error Response: ' . $content);
                return ['error' => 'Erreur API: ' . $content];
            }

            $data = $response->toArray();
            $forecasts = [];
            $processedDates = [];

            foreach ($data['list'] as $item) {
                $date = date('Y-m-d', $item['dt']);
                
                // Only take one forecast per day (at noon)
                if (!in_array($date, $processedDates) && date('H', $item['dt']) == '12') {
                    $processedDates[] = $date;
                    $forecasts[] = [
                        'date' => date('d/m', $item['dt']),
                        'day' => date('D', $item['dt']),
                        'temperature' => round($item['main']['temp']),
                        'description' => $item['weather'][0]['description'],
                        'icon' => $item['weather'][0]['icon']
                    ];
                }
            }

            $this->logger->info('Successfully fetched forecast data for: ' . $formattedCity);
            return array_slice($forecasts, 0, 5); // Return only 5 days
        } catch (\Exception $e) {
            $this->logger->error('Error fetching forecast for ' . $city . ': ' . $e->getMessage());
            return ['error' => 'Impossible de récupérer les prévisions météo pour ' . $city . ': ' . $e->getMessage()];
        }
    }
} 