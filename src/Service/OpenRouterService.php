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

    public function generateComment(string $prompt): string
    {
        try {
            $this->logger->info('Sending request to OpenRouter API', [
                'prompt' => $prompt,
                'model' => 'deepseek/deepseek-v3-base:free'
            ]);

            $response = $this->client->request('POST', $this->apiUrl . '/chat/completions', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json' => [
                    'model' => 'deepseek/deepseek-v3-base:free',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un bot d\'agence de voyage. Fournis des commentaires brefs et amicaux en français sur les annonces et sondages liés aux voyages.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 150,
                    'stream' => false
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent();
            
            $this->logger->info('OpenRouter API response', [
                'status_code' => $statusCode,
                'content' => $content
            ]);

            $data = json_decode($content, true);
            
            if (isset($data['error'])) {
                $errorMessage = $data['error']['message'] ?? 'Unknown API error';
                $this->logger->error('API Error: ' . $errorMessage);
                throw new \RuntimeException('API Error: ' . $errorMessage);
            }

            if (!isset($data['choices']) || empty($data['choices'])) {
                $this->logger->error('Invalid response format from OpenRouter API', [
                    'data' => $data
                ]);
                throw new \RuntimeException('Invalid response format from OpenRouter API');
            }

            $botComment = $data['choices'][0]['message']['content'] ?? '';
            
            if (empty($botComment)) {
                $this->logger->error('Empty message in API response', [
                    'data' => $data
                ]);
                throw new \RuntimeException('Empty message in API response');
            }

            // Format the final comment
            $finalComment = "Bonjour et bienvenue sur le forum !\n" .
                          "TripToGo, votre agence de voyage, vous souhaite une excellente expérience.\n\n" .
                          $botComment . "\n\n" .
                          "Je suis un bot, et cette action a été effectuée automatiquement.\n" .
                          "Veuillez contacter les modérateurs du forum pour toute question ou préoccupation.";

            return $finalComment;
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('OpenRouter API error: ' . $e->getMessage());
            throw new \RuntimeException('Failed to generate comment: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error: ' . $e->getMessage());
            throw new \RuntimeException('Unexpected error: ' . $e->getMessage());
        }
    }
} 