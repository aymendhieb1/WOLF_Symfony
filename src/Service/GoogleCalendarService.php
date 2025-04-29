<?php

namespace App\Service;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GoogleCalendarService
{
    private $client;
    private $service;
    private $params;

    public function __construct(string $clientId, string $clientSecret, string $redirectUri)
    {
        $this->client = new Google_Client();
        $this->client->setClientId($clientId);
        $this->client->setClientSecret($clientSecret);
        $this->client->setRedirectUri($redirectUri);
        $this->client->addScope(Google_Service_Calendar::CALENDAR);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    public function getAccessToken($code)
    {
        return $this->client->fetchAccessTokenWithAuthCode($code);
    }

    public function setAccessToken($token)
    {
        $this->client->setAccessToken($token);
        $this->service = new Google_Service_Calendar($this->client);
    }

    public function createEvent($title, $startDate, $endDate, $description = '', $colorId = null)
    {
        if (!$this->service) {
            throw new \Exception('Google Calendar service not initialized. Please authenticate first.');
        }

        $event = new \Google_Service_Calendar_Event([
            'summary' => $title,
            'description' => $description,
            'start' => [
                'dateTime' => $startDate->format('c'),
                'timeZone' => 'Africa/Tunis',
            ],
            'end' => [
                'dateTime' => $endDate->format('c'),
                'timeZone' => 'Africa/Tunis',
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => 24 * 60],
                    ['method' => 'popup', 'minutes' => 60],
                ],
            ],
        ]);

        if ($colorId !== null) {
            $event->setColorId($colorId);
        }

        return $this->service->events->insert('primary', $event);
    }
}