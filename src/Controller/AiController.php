<?php

namespace App\Controller;

use App\Service\OpenRouterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/api/ai', name: 'api_ai_')]
class AiController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/chat', name: 'chat', methods: ['POST'])]
    public function chat(Request $request, OpenRouterService $openRouterService): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['messages']) || !is_array($data['messages'])) {
                return $this->json(['error' => 'Invalid request: messages array is required'], Response::HTTP_BAD_REQUEST);
            }

            $messages = $data['messages'];
            $model = $data['model'] ?? 'deepseek/deepseek-v3-base:free';

            $response = $openRouterService->generateResponse($messages, $model);
            
            // Log the response for debugging
            $this->logger->info('OpenRouter API response', ['response' => $response]);
            
            return $this->json($response);
        } catch (\Exception $e) {
            $this->logger->error('Chat error: ' . $e->getMessage());
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/models', name: 'models', methods: ['GET'])]
    public function listModels(OpenRouterService $openRouterService): JsonResponse
    {
        try {
            $models = $openRouterService->listModels();
            return $this->json($models);
        } catch (\Exception $e) {
            $this->logger->error('Models list error: ' . $e->getMessage());
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 