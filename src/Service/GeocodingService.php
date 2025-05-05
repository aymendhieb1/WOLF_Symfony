<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeocodingService
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function getCoordinates(string $city): ?array
    {
        // Call geocoding API (using Nominatim for example)
        $response = $this->client->request('GET', 'https://nominatim.openstreetmap.org/search', [
            'query' => [
                'q' => $city,
                'format' => 'json',
                'addressdetails' => 1,
                'limit' => 1
            ]
        ]);

        // Check if the response has data
        $data = $response->toArray();
        if (empty($data)) {
            return null;
        }

        // Return the coordinates as an associative array
        return [
            'latitude' => $data[0]['lat'],
            'longitude' => $data[0]['lon'],
            'label' => $data[0]['display_name'],  // optional, for displaying the name of the location
        ];
    }
}
