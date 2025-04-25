<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProxyController extends AbstractController
{
    #[Route('/api/proxy/dlnk', name: 'app_proxy_dlnk', methods: ['GET'])]
    public function proxyDlnk(Request $request): Response
    {
        try {
            $client = HttpClient::create();
            $id = $request->query->get('id');
            $type = $request->query->get('type', '1');
            
            if (!$id) {
                return new JsonResponse(['error' => 'Missing required parameter: id'], 400);
            }

            $url = "https://dlnk.one/e?id={$id}&type={$type}";
            $response = $client->request('GET', $url);
            
            return new Response(
                $response->getContent(),
                $response->getStatusCode(),
                ['Content-Type' => $response->getHeaders()['content-type'][0] ?? 'application/json']
            );
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
} 