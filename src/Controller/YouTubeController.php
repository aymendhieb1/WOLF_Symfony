<?php

namespace App\Controller;

use App\Service\YouTubeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class YouTubeController extends AbstractController
{
    private YouTubeService $youtubeService;

    public function __construct(YouTubeService $youtubeService)
    {
        $this->youtubeService = $youtubeService;
    }

    #[Route('/youtube', name: 'app_youtube')]
    public function index(): Response
    {
        return $this->render('youtube/index.html.twig', [
            'playlistUrl' => $this->youtubeService->getPlaylistUrl(),
            'videos' => $this->youtubeService->getVideos()
        ]);
    }

    #[Route('/youtube/api/videos', name: 'app_youtube_api')]
    public function getVideosApi(): JsonResponse
    {
        return new JsonResponse($this->youtubeService->getVideos());
    }
}