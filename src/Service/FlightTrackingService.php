<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class FlightTrackingService
{
    private $client;
    private $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    public function getAircraftData()
    {
        // URL for real-time flight data (AviationStack endpoint)
        $url = 'http://api.aviationstack.com/v1/flights?access_key=' . $this->apiKey;

        // Fetch the data from the API
        $response = $this->client->request('GET', $url);

        // Parse the response
        $data = $response->toArray();

        return $data['data'];  // Assuming 'data' contains the flight info
    }
}
