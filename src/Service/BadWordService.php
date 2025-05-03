<?php

namespace App\Service;

use App\Entity\Post;
use App\Entity\Comment;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

class BadWordService
{
    private const API_KEY = 'AIzaSyD2NWsmP1XlVcqptTeLGmvpbB-MDM57vAo';
    private const API_URL = 'https://commentanalyzer.googleapis.com/v1alpha1/comments:analyze';
    private const TOXICITY_THRESHOLD = 0.7;

<<<<<<< Updated upstream
    /**
     * Validates if a text contains inappropriate content
     *
     * @param string $text The text to validate
     * @return bool True if inappropriate content is detected, false otherwise
     */
=======
>>>>>>> Stashed changes
    public function containsBadWords(string $text): bool
    {
        if (empty(trim($text))) {
            return false;
        }

        try {
            $client = HttpClient::create();
            $response = $client->request('POST', self::API_URL . '?key=' . self::API_KEY, [
                'json' => [
                    'comment' => ['text' => $text],
                    'languages' => ['fr'],
                    'requestedAttributes' => [
                        'TOXICITY' => new \stdClass(),
                        'SEVERE_TOXICITY' => new \stdClass(),
                        'INSULT' => new \stdClass(),
                        'PROFANITY' => new \stdClass()
                    ]
                ]
            ]);

            $data = json_decode($response->getContent(), true);
            $attributeScores = $data['attributeScores'] ?? [];

            foreach ($attributeScores as $attribute => $scoreData) {
                $score = $scoreData['summaryScore']['value'] ?? 0;
                if ($score > self::TOXICITY_THRESHOLD) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
<<<<<<< Updated upstream
            // Log the error but don't block the content
            // You might want to add proper logging here
=======

>>>>>>> Stashed changes
            return false;
        }
    }

<<<<<<< Updated upstream
    /**
     * Validates if a Post entity contains inappropriate content
     *
     * @param Post $post The post to validate
     * @return bool True if inappropriate content is detected, false otherwise
     */
=======

>>>>>>> Stashed changes
    public function validatePost(Post $post): bool
    {
        if ($post->getType() === 'announcement') {
            return $this->containsBadWords($post->getAnnouncementTitle()) ||
                   $this->containsBadWords($post->getAnnouncementContent()) ||
                   $this->containsBadWords($post->getAnnouncementTags());
        } elseif ($post->getType() === 'survey') {
            return $this->containsBadWords($post->getSurveyQuestion()) ||
                   $this->containsBadWords($post->getSurveyTags());
        }
        
        return false;
    }

<<<<<<< Updated upstream
    /**
     * Validates if a Comment entity contains inappropriate content
     *
     * @param Comment $comment The comment to validate
     * @return bool True if inappropriate content is detected, false otherwise
     */
=======

>>>>>>> Stashed changes
    public function validateComment(Comment $comment): bool
    {
        return $this->containsBadWords($comment->getCommentContent());
    }

<<<<<<< Updated upstream
    /**
     * Validates any text content for inappropriate language
     *
     * @param string $content The content to validate
     * @return array [
     *     'is_valid' => bool,
     *     'message' => string,
     *     'scores' => array
     * ]
     */
=======

>>>>>>> Stashed changes
    public function validateContent(string $content): array
    {
        if (empty(trim($content))) {
            return [
                'is_valid' => true,
                'message' => 'Content is empty',
                'scores' => []
            ];
        }

        try {
            $client = HttpClient::create();
            $response = $client->request('POST', self::API_URL . '?key=' . self::API_KEY, [
                'json' => [
                    'comment' => ['text' => $content],
                    'languages' => ['fr'],
                    'requestedAttributes' => [
                        'TOXICITY' => new \stdClass(),
                        'SEVERE_TOXICITY' => new \stdClass(),
                        'INSULT' => new \stdClass(),
                        'PROFANITY' => new \stdClass()
                    ]
                ]
            ]);

            $data = json_decode($response->getContent(), true);
            $attributeScores = $data['attributeScores'] ?? [];
            $scores = [];
            $isValid = true;

            foreach ($attributeScores as $attribute => $scoreData) {
                $score = $scoreData['summaryScore']['value'] ?? 0;
                $scores[$attribute] = $score;
                if ($score > self::TOXICITY_THRESHOLD) {
                    $isValid = false;
                }
            }

            return [
                'is_valid' => $isValid,
                'message' => $isValid ? 'Content is appropriate' : 'Content contains inappropriate language',
                'scores' => $scores
            ];
        } catch (\Exception $e) {
            return [
                'is_valid' => false,
                'message' => 'Error validating content: ' . $e->getMessage(),
                'scores' => []
            ];
        }
    }
} 