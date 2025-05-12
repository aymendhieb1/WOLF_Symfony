<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CountryFlagExtension extends AbstractExtension
{
    private $httpClient;
    private $cache = [];

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('country_flag', [$this, 'getCountryFlag']),
        ];
    }

    private function getCountryCode(string $location): ?string
    {
        // Check cache first
        if (isset($this->cache[$location])) {
            return $this->cache[$location];
        }

        try {
            // Use OpenStreetMap's Nominatim API to get location data
            $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $location,
                    'format' => 'json',
                    'limit' => 1,
                    'addressdetails' => 1
                ],
                'headers' => [
                    'User-Agent' => 'Symfony/YourApp' // Required by Nominatim's terms of use
                ]
            ]);

            $data = $response->toArray();
            
            if (!empty($data)) {
                $countryCode = $data[0]['address']['country_code'] ?? null;
                if ($countryCode) {
                    // Cache the result
                    $this->cache[$location] = strtoupper($countryCode);
                    return strtoupper($countryCode);
                }
            }
        } catch (\Exception $e) {
            // Log error if needed
            return null;
        }

        return null;
    }

    public function getCountryFlag(?string $location): string
    {
        if (!$location) {
            return '';
        }

        $countryCode = $this->getCountryCode($location);
        if ($countryCode) {
            // Convert country code to flag emoji
            $flag = mb_convert_encoding('&#' . (127397 + ord($countryCode[0])) . ';&#' . (127397 + ord($countryCode[1])) . ';', 'UTF-8', 'HTML-ENTITIES');
            return $flag;
        }

        return '';
    }
} 