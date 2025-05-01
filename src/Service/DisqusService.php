<?php

namespace App\Service;

use App\Entity\Post;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

class DisqusService
{
    private const DISQUS_SHORTNAME = 'triptogo-1';
    private const DISQUS_ACCESS_TOKEN = 'c00bec3edb2a47348566950df8a88620';
    private const DISQUS_API_KEY = 'Xu4KJbiIVXplrE5CA5rWKHDgLfhQVrY4fPJIrGBSDlTvCnlnzi5FO94VnUtNoWhA';
    private const DEFAULT_THREAD_ID = '10527551196'; 

    private $logger;
    private $lastThreadId = null;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getConfig(Post $post): array
    {
        return [
            'shortname' => self::DISQUS_SHORTNAME,
            'url' => $this->getPostUrl($post),
            'identifier' => 'post_' . $post->getPostId(),
            'title' => $this->getPostTitle($post)
        ];
    }

    public function createComment(string $threadId, string $message): array
    {
        try {
            $client = HttpClient::create();
            $response = $client->request('POST', 'https://disqus.com/api/3.0/posts/create.json', [
                'query' => [
                    'api_key' => self::DISQUS_API_KEY,
                    'access_token' => self::DISQUS_ACCESS_TOKEN,
                    'thread' => $threadId,
                    'message' => $message
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Origin' => 'https://disqus.com'
                ]
            ]);

            $data = json_decode($response->getContent(), true);

            if (isset($data['response'])) {
                return [
                    'success' => true,
                    'comment' => $data['response']
                ];
            } else if (isset($data['error'])) {
                throw new \Exception($data['error']['message'] ?? 'Unknown error from Disqus API');
            } else {
                throw new \Exception('Invalid response from Disqus API');
            }
        } catch (\Exception $e) {
            $this->logger->error('Error creating Disqus comment: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getLastThreadId(): ?string
    {
        return $this->lastThreadId;
    }

    public function setLastThreadId(?string $threadId): void
    {
        $this->lastThreadId = $threadId;
    }

    public function getDefaultThreadId(): string
    {
        return self::DEFAULT_THREAD_ID;
    }

    public function getPostTitle(Post $post): string
    {
        if ($post->getType() === 'announcement') {
            return $post->getAnnouncementTitle() ?? 'Untitled Announcement';
        } elseif ($post->getType() === 'survey') {
            return $post->getSurveyQuestion() ?? 'Untitled Survey';
        }
        return 'Untitled Post';
    }

    private function getPostUrl(Post $post): string
    {
        return sprintf('http://127.0.0.1:8000/front/post/%d', $post->getPostId());
    }

    public function createThread(Post $post): array
    {
        try {
            $client = HttpClient::create();
            $response = $client->request('POST', 'https://disqus.com/api/3.0/threads/create.json', [
                'query' => [
                    'api_key' => self::DISQUS_API_KEY,
                    'access_token' => self::DISQUS_ACCESS_TOKEN,
                    'forum' => self::DISQUS_SHORTNAME,
                    'title' => $this->getPostTitle($post),
                    'url' => $this->getPostUrl($post),
                    'identifier' => 'post_' . $post->getPostId()
                ]
            ]);

            $data = json_decode($response->getContent(), true);

            if (isset($data['response'])) {
                $this->lastThreadId = $data['response']['id'];
                return [
                    'success' => true,
                    'thread' => $data['response']
                ];
            } else if (isset($data['error'])) {
                throw new \Exception($data['error']['message'] ?? 'Unknown error from Disqus API');
            } else {
                throw new \Exception('Invalid response from Disqus API');
            }
        } catch (\Exception $e) {
            $this->logger->error('Error creating Disqus thread: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function createThreadWithComment(Post $post, string $initialComment): array
    {
        $threadResult = $this->createThread($post);

        if (!$threadResult['success']) {
            return $threadResult;
        }

        $commentResult = $this->createComment($threadResult['thread']['id'], $initialComment);

        if (!$commentResult['success']) {
            return [
                'success' => false,
                'error' => 'Thread created but comment failed: ' . $commentResult['error']
            ];
        }

        return [
            'success' => true,
            'thread' => $threadResult['thread'],
            'comment' => $commentResult['comment']
        ];
    }
}