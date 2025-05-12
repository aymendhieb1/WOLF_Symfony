<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CityImagesExtension extends AbstractExtension
{
    private $httpClient;
    private $unsplashAccessKey;

    public function __construct(HttpClientInterface $httpClient, string $unsplashAccessKey)
    {
        $this->httpClient = $httpClient;
        $this->unsplashAccessKey = $unsplashAccessKey;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('city_images', [$this, 'getCityImages']),
        ];
    }

    public function getCityImages(string $city): array
    {
        try {
            // Normalize city name
            $city = $this->normalizeCityName($city);
            
            // Fetch images from Unsplash API
            $response = $this->httpClient->request('GET', 'https://api.unsplash.com/search/photos', [
                'query' => [
                    'query' => $city . ' city landmarks',
                    'per_page' => 10,
                    'orientation' => 'landscape'
                ],
                'headers' => [
                    'Authorization' => 'Client-ID ' . $this->unsplashAccessKey
                ]
            ]);

            $data = $response->toArray();
            
            // Format the response
            $images = [];
            foreach ($data['results'] as $result) {
                $images[] = [
                    'url' => $result['urls']['regular'],
                    'credit' => 'Photo by ' . $result['user']['name'] . ' on Unsplash'
                ];
            }

            return $images;
        } catch (\Exception $e) {
            // Return default images if API call fails
            return $this->getDefaultImages();
        }
    }

    private function normalizeCityName(string $city): string
    {
        // Convert to lowercase and remove accents
        $city = strtolower($city);
        $city = str_replace(
            ['à','á','â','ã','ä','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ú','û','ü','ý','ÿ'],
            ['a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','u','y','y'],
            $city
        );
        
        // Capitalize first letter
        return ucfirst($city);
    }

    private function getDefaultImages(): array
    {
        return [
            [
                'url' => 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34',
                'credit' => 'Photo by Unsplash'
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1499856871958-5b9627545d1a',
                'credit' => 'Photo by Unsplash'
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1480714378408-67cf0d13bc1b',
                'credit' => 'Photo by Unsplash'
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1477959858617-67f85cf4f1df',
                'credit' => 'Photo by Unsplash'
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1444723121867-7a241cacace9',
                'credit' => 'Photo by Unsplash'
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000',
                'credit' => 'Photo by Unsplash'
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1444723121867-7a241cacace9',
                'credit' => 'Photo by Unsplash'
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000',
                'credit' => 'Photo by Unsplash'
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1477959858617-67f85cf4f1df',
                'credit' => 'Photo by Unsplash'
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1444723121867-7a241cacace9',
                'credit' => 'Photo by Unsplash'
            ]
        ];
    }
} 