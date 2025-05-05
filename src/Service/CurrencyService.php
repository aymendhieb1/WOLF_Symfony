<?php

// src/Service/CurrencyService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class CurrencyService
{
    private HttpClientInterface $client;
    private string $apiKey;
    private CacheInterface $cache;

    public function __construct(HttpClientInterface $client, CacheInterface $cache, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
        $this->cache = $cache;
    }

    public function getExchangeRates(string $baseCurrency = 'TND'): array
    {
        return $this->cache->get('exchange_rates_' . $baseCurrency, function (ItemInterface $item) use ($baseCurrency) {
            $item->expiresAfter(1800); // Cache for 30 minutes (1800 seconds)

            try {
                $url = "https://v6.exchangerate-api.com/v6/{$this->apiKey}/latest/{$baseCurrency}";
                $response = $this->client->request('GET', $url);
                $data = $response->toArray();

                if (isset($data['conversion_rates'])) {
                    return $data['conversion_rates'];
                } else {
                    return []; // Return empty array if no conversion rates found
                }
            } catch (TransportExceptionInterface $e) {
                return []; // Handle any transport exception (e.g., log it)
            }
        });
    }
}
