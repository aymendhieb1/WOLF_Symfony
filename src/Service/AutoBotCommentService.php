<?php

namespace App\Service;

use App\Entity\Post;
use App\Entity\Comment;
use App\Repository\PostRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

# OpenRouter API Configuration
define('OPENROUTER_API_URL', 'https://openrouter.ai/api/v1/chat/completions');
define('OPENROUTER_API_KEY', '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');

class AutoBotCommentService
{
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private PostRepository $postRepository;
    private CommentRepository $commentRepository;

    public function __construct(
        HttpClientInterface $httpClient,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        PostRepository $postRepository,
        CommentRepository $commentRepository
    ) {
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->postRepository = $postRepository;
        $this->commentRepository = $commentRepository;
    }

    public function autoCommentOnPost(Post $post): ?string
    {
        try {
            // Check if post already has a bot comment
            $existingComment = $this->commentRepository->findOneBy([
                'post' => $post,
                'isBotComment' => true
            ]);

            if ($existingComment) {
                return $existingComment->getContent();
            }

            // Prepare content based on post type
            $content = $post->getType() === 'announcement' 
                ? $post->getAnnouncementTitle() . "\n" . $post->getAnnouncementContent()
                : $post->getSurveyQuestion();

            // Generate AI comment using OpenRouter API
            $response = $this->httpClient->request('POST', OPENROUTER_API_URL, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . OPENROUTER_API_KEY
                ],
                'body' => json_encode([
                    'model' => 'deepseek/deepseek-v3-base:free',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => "Imagine Tu es un bot d'agence de voyage. Commenter bref dans ce (Annonce ou Sondage) " . $content
                        ]
                    ]
                ])
            ]);

            $data = json_decode($response->getContent(), true);
            
            if (!isset($data['choices'][0]['message']['content'])) {
                throw new \Exception('Invalid response from OpenRouter API');
            }

            $botComment = $data['choices'][0]['message']['content'];

            // Construct final comment
            $finalComment = "Bonjour et bienvenue sur le forum " . $post->getForum()->getName() . " !\n"
                . "TripToGo, votre agence de voyage, vous souhaite une excellente expérience.\n\n"
                . $botComment . "\n\n"
                . "Je suis un bot, et cette action a été effectuée automatiquement.\n"
                . "Veuillez contacter les modérateurs du forum pour toute question ou préoccupation.";

            // Create and save the comment
            $comment = new Comment();
            $comment->setPost($post)
                ->setContent($finalComment)
                ->setIsBotComment(true)
                ->setCreatedAt(new \DateTime());

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            return $finalComment;
        } catch (\Exception $e) {
            $this->logger->error('Error generating auto comment: ' . $e->getMessage());
            return null;
        }
    }
} 