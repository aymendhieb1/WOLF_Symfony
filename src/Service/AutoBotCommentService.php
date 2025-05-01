<?php

namespace App\Service;

use App\Entity\Post;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AutoBotCommentService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private PostRepository $postRepository;
    private OpenRouterService $openRouterService;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        PostRepository $postRepository,
        OpenRouterService $openRouterService
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->postRepository = $postRepository;
        $this->openRouterService = $openRouterService;
    }

    public function generateAutoComment(Post $post): array
    {
        try {
            $this->logger->info('Generating auto-comment for post', [
                'postId' => $post->getPostId(),
                'type' => $post->getType(),
                'title' => $post->getAnnouncementTitle(),
                'content' => $post->getAnnouncementContent(),
                'question' => $post->getSurveyQuestion()
            ]);

            $postContent = $this->extractPostContent($post);
            $this->logger->info('Extracted post content', ['content' => $postContent]);

            $rawResponse = $this->generateAiComment($postContent);
            $this->logger->info('Raw API response', ['response' => $rawResponse]);

            // Extract just the content from the response
            $botComment = $this->extractContentFromResponse($rawResponse);
            $this->logger->info('Extracted bot comment', ['comment' => $botComment]);

            $finalComment = $this->formatFinalComment($botComment, $post);
            $this->logger->info('Formatted final comment', ['finalComment' => $finalComment]);

            return [
                'success' => true,
                'comment' => $finalComment,
                'debug' => [
                    'postContent' => $postContent,
                    'rawApiResponse' => $rawResponse,
                    'extractedContent' => $botComment,
                    'postDetails' => [
                        'id' => $post->getPostId(),
                        'type' => $post->getType(),
                        'title' => $post->getAnnouncementTitle(),
                        'content' => $post->getAnnouncementContent(),
                        'question' => $post->getSurveyQuestion(),
                        'forumName' => $post->getForumId()->getName()
                    ]
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error in auto-comment generation: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function extractPostContent(Post $post): string
    {
            if ($post->getType() === 'announcement') {
            return "Annonce Titre: " . $post->getAnnouncementTitle() . "\n" .
                             "Annonce Contenu: " . $post->getAnnouncementContent();
            } else if ($post->getType() === 'survey') {
                $choices = $post->getChoixs()->map(function($choix) {
                    return $choix->getChoix();
                })->toArray();
                
            return "Sondage Question: " . $post->getSurveyQuestion() . "\n" .
                             "Choix: " . implode(", ", $choices);
            }

        return "";
    }

    private function generateAiComment(string $postContent): array
    {
        $systemPrompt = "Imagine Tu es un bot d'agence de voyage. Commenter bref dans ce contenu";
        try {
            $response = $this->openRouterService->generateResponse([
                ['role' => 'user', 'content' => $systemPrompt . "\n\n" . $postContent]
            ]);
            
            $this->logger->info('OpenRouter API response', ['response' => $response]);
            
            // Clean up the response content
            if (isset($response['choices'][0]['message']['content'])) {
                $content = $response['choices'][0]['message']['content'];
                
                // Remove the system prompt if it appears in the response
                $content = str_replace($systemPrompt, '', $content);
                
                // Remove any HTML tags
                $content = strip_tags($content);
                // Remove any email addresses
                $content = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '', $content);
                // Remove any phone numbers
                $content = preg_replace('/\b\d{10,}\b/', '', $content);
                // Remove any URLs
                $content = preg_replace('/\bhttps?:\/\/\S+\b/', '', $content);
                // Remove any duplicate content
                $content = preg_replace('/\b(Annonce Titre:|Annonce Contenu:|Contenu:|Trip Highlights:|Préfixe:).*?(?=\n|$)/s', '', $content);
                // Remove any asterisks or special characters
                $content = preg_replace('/[*]+/', '', $content);
                // Remove any HTML line breaks
                $content = str_replace(['<br/>', '<br>', '<br />'], "\n", $content);
                // Remove any duplicate newlines
                $content = preg_replace('/\n\s*\n/', "\n\n", $content);
                // Trim whitespace
                $content = trim($content);
                
                $response['choices'][0]['message']['content'] = $content;
            }
            
            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Error generating AI comment: ' . $e->getMessage());
            return [
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => "⚠️ Erreur lors de la génération du commentaire: " . $e->getMessage()
                        ]
                    ]
                ]
            ];
        }
    }

    private function extractContentFromResponse(array $response): string
    {
        if (isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
            // Ensure proper line breaks
            $content = str_replace("\n\n\n", "\n\n", $content);
            $content = str_replace("\n \n", "\n\n", $content);
            $content = str_replace("\n\n \n", "\n\n", $content);
            return trim($content);
        }
        return "⚠️ Erreur: Impossible d'extraire le contenu de la réponse.";
    }

    private function formatFinalComment(string $botComment, Post $post): string
    {
        $forumName = $post->getForumId()->getName();
        
        $finalComment = "Bonjour et bienvenue sur le forum {$forumName} !\n\n" .
                          "TripToGo, votre agence de voyage, vous souhaite une excellente expérience.\n\n" .
                          trim($botComment) . "\n\n" .
                          "Je suis un bot, et cette action a été effectuée automatiquement.\n" .
                          "Veuillez contacter les modérateurs du forum pour toute question ou préoccupation.";

        // Ensure proper line breaks
        $finalComment = str_replace("\n\n\n", "\n\n", $finalComment);
        $finalComment = str_replace("\n \n", "\n\n", $finalComment);
        $finalComment = str_replace("\n\n \n", "\n\n", $finalComment);
        $finalComment = str_replace("\n\n\n", "\n\n", $finalComment);

        return empty($finalComment) ? "⚠️ Erreur: Impossible de générer le commentaire." : $finalComment;
    }
}