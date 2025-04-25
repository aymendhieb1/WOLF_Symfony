<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

class OpenRouterService
{
    private HttpClientInterface $client;
    private string $apiUrl;
    private string $apiKey;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $client,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->apiUrl = 'https://openrouter.ai/api/v1';
        $this->apiKey = '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';
    }

    public function generateResponse(array $messages, string $model = 'deepseek/deepseek-v3-base:free'): array
    {
        try {
            $response = $this->client->request('POST', $this->apiUrl . '/chat/completions', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                ],
            ]);

            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('OpenRouter API error: ' . $e->getMessage());
            throw new \RuntimeException('Failed to communicate with OpenRouter API: ' . $e->getMessage());
        }
    }

    public function listModels(): array
    {
        try {
            $response = $this->client->request('GET', $this->apiUrl . '/models', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
            ]);

            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('OpenRouter API error: ' . $e->getMessage());
            throw new \RuntimeException('Failed to fetch models from OpenRouter API: ' . $e->getMessage());
        }
    }
} 