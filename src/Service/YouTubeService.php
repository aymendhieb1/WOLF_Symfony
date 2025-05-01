<?php

namespace App\Service;

class YouTubeService
{
    private const API_KEY = 'AIzaSyDeCQi_RXKiEQDjhWP3hJQUfkecUhyywSA';
    private const PLAYLIST_ID = 'PLfSfQ-u0IZ4T919z1Kaeat4e2N75GmsQR';
    public const REFRESH_INTERVAL = 60; 

    public function getVideos(): array
    {
        try {
            $url = sprintf(
                'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId=%s&maxResults=50&key=%s',
                self::PLAYLIST_ID,
                self::API_KEY
            );

            error_log('YouTubeService: Fetching from URL: ' . $url);
            $response = file_get_contents($url);

            if ($response === false) {
                throw new \Exception('Failed to fetch YouTube API');
            }

            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            error_log('YouTubeService: Raw API response: ' . print_r($data, true));

            if (!isset($data['items']) || empty($data['items'])) {
                throw new \Exception('No items found in playlist response');
            }

            $videos = [];
            foreach ($data['items'] as $item) {
                if (!isset($item['snippet']['resourceId']['videoId'], 
                          $item['snippet']['title'],
                          $item['snippet']['thumbnails']['default']['url'])) {
                    error_log('YouTubeService: Skipping invalid video item: ' . print_r($item, true));
                    continue;
                }

                $videoId = $item['snippet']['resourceId']['videoId'];
                $videos[] = [
                    'id' => $videoId,
                    'title' => $item['snippet']['title'],
                    'thumbnail' => $item['snippet']['thumbnails']['default']['url'],
                    'description' => $item['snippet']['description'] ?? '',
                    'publishedAt' => $item['snippet']['publishedAt'] ?? '',
                    'position' => $item['snippet']['position'] ?? 0,
                    'embedUrl' => $this->getVideoEmbedUrl($videoId)
                ];
            }

            if (empty($videos)) {
                throw new \Exception('No valid videos found in playlist items');
            }

            error_log(sprintf('YouTubeService: Processed %d videos', count($videos)));
            error_log('YouTubeService: First video sample: ' . print_r($videos[0], true));

            return $videos;

        } catch (\Exception $e) {
            error_log('YouTube API Error: ' . $e->getMessage());
            return [];
        }
    }

    private function getVideoEmbedUrl(string $videoId): string
    {
        return sprintf(
            'https://www.youtube.com/embed/%s?autoplay=0&rel=0&modestbranding=1',
            $videoId
        );
    }

    public function getPlaylistUrl(): string
    {
        return sprintf(
            'https://www.youtube.com/embed/videoseries?list=%s&autoplay=1&loop=1&playlist=%s',
            self::PLAYLIST_ID,
            self::PLAYLIST_ID
        );
    }
}