<?php

namespace App\Controller;

use App\Service\OpenRouterService;
use App\Service\AutoBotCommentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use App\Entity\Post;

#[Route('/api/ai', name: 'api_ai_')]
class AiController extends AbstractController
{
    private LoggerInterface $logger;
    private AutoBotCommentService $autoBotCommentService;

    public function __construct(
        LoggerInterface $logger,
        AutoBotCommentService $autoBotCommentService
    ) {
        $this->logger = $logger;
        $this->autoBotCommentService = $autoBotCommentService;
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

    #[Route('/post/{id}/auto-comment', name: 'auto_comment', methods: ['POST'])]
    public function autoComment(Post $post): JsonResponse
    {
        $this->logger->info('Auto-comment request received', [
            'postId' => $post->getPostId(),
            'type' => $post->getType()
        ]);

        $result = $this->autoBotCommentService->generateAutoComment($post);
        
        if (!$result['success']) {
            $this->logger->error('Auto-comment generation failed', [
                'error' => $result['error'],
                'postId' => $post->getPostId()
            ]);
            
            return $this->json([
                'success' => false,
                'error' => $result['error']
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->logger->info('Auto-comment generated successfully', [
            'postId' => $post->getPostId(),
            'comment' => $result['comment']
        ]);

        return $this->json([
            'success' => true,
            'comment' => $result['comment'],
            'debug' => $result['debug'] ?? null
        ]);
    }

    #[Route('/api/ai/test', name: 'app_ai_test', methods: ['GET'])]
    public function testApi(OpenRouterService $openRouterService): JsonResponse
    {
        try {
            $prompt = "This is a test prompt to check if the AI service is working correctly.";
            $response = $openRouterService->generateComment($prompt);
            
            return $this->json([
                'success' => true,
                'message' => 'API test successful',
                'data' => [
                    'content' => $response,
                    'length' => strlen($response),
                    'is_empty' => empty($response)
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('API test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->json([
                'success' => false,
                'message' => 'API test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/test-models', name: 'test_models', methods: ['GET'])]
    public function testModels(OpenRouterService $openRouterService): JsonResponse
    {
        try {
            $models = $openRouterService->listModels();
            
            return $this->json([
                'success' => true,
                'message' => 'Models retrieved successfully',
                'models' => $models
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'details' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 