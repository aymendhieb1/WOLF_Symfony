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
        $systemPrompt = "Tu es un bot d'agence de voyage. Commenter bref dans ce contenu repondu en français essayez d'être serviable et amical";
        try {
            $response = $this->openRouterService->generateResponse([
                ['role' => 'user', 'content' => $systemPrompt . "\n\n" . $postContent]
            ]);

            $this->logger->info('OpenRouter API response', ['response' => $response]);

            if (isset($response['choices'][0]['message']['content'])) {
                $content = $response['choices'][0]['message']['content'];

                $content = str_replace($systemPrompt, '', $content);

                $content = strip_tags($content);

                $content = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '', $content);

                $content = preg_replace('/\b\d{10,}\b/', '', $content);

                $content = preg_replace('/\bhttps?:\/\/\S+\b/', '', $content);

                $content = preg_replace('/\b(Annonce Titre:|Annonce Contenu:|Contenu:|Trip Highlights:|Préfixe:).*?(?=\n|$)/s', '', $content);

                $content = preg_replace('/[*]+/', '', $content);

                $content = str_replace(['<br/>', '<br>', '<br />'], "\n", $content);

                $content = preg_replace('/\n\s*\n/', "\n\n", $content);

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
                          "Veuillez contacter les modérateurs du forum pour toute question ou préoccupation.";

        $finalComment = str_replace("\n\n\n", "\n\n", $finalComment);
        $finalComment = str_replace("\n \n", "\n\n", $finalComment);
        $finalComment = str_replace("\n\n \n", "\n\n", $finalComment);
        $finalComment = str_replace("\n\n\n", "\n\n", $finalComment);

        return empty($finalComment) ? "⚠️ Erreur: Impossible de générer le commentaire." : $finalComment;
    }
}