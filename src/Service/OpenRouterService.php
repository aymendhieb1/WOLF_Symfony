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

    public function generateResponse(array $messages, string $model = 'deepseek/deepseek-chat:free'): array
    {
        try {
            $this->logger->info('Sending request to OpenRouter API', [
                'model' => $model,
                'messages' => $messages
            ]);

            $response = $this->client->request('POST', $this->apiUrl . '/chat/completions', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'HTTP-Referer' => 'https://triptogo.com',
                    'X-Title' => 'TripToGo Bot'
                ],
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                    'max_tokens' => 100,
                    'stream' => false,
                    'metadata' => [
                        'language' => 'fr'
                    ]
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent();

            $this->logger->info('OpenRouter API response', [
                'status_code' => $statusCode,
                'content' => $content
            ]);

            if ($statusCode !== 200) {
                throw new \RuntimeException('OpenRouter API returned non-200 status code: ' . $statusCode);
            }

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Failed to decode API response: ' . json_last_error_msg());
            }

            if (isset($data['error'])) {
                throw new \RuntimeException('API Error: ' . ($data['error']['message'] ?? 'Unknown error'));
            }

            return $data;
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('OpenRouter API error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Failed to communicate with OpenRouter API: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error in OpenRouter API call: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Unexpected error in OpenRouter API call: ' . $e->getMessage());
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