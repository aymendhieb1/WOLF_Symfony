<?php

namespace App\Controller;

use App\Service\AutocompleteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AutocompleteController extends AbstractController
{
    public function __construct(
        private AutocompleteService $autocompleteService
    ) {}

    #[Route('/api/autocomplete', name: 'app_autocomplete', methods: ['GET'])]
    public function getSuggestions(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $source = $request->query->get('source', 'google');
        $entity = $request->query->get('entity');
        $searchField = $request->query->get('field', 'name');

        $debug = [
            'query' => $query,
            'source' => $source,
            'entity' => $entity,
            'searchField' => $searchField,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ];

        try {

            if ($source === 'database' && !$entity) {
                $source = 'google'; 
            }

            $suggestions = $this->autocompleteService->getSuggestions($query, $source, $entity, $searchField);

            $response = new JsonResponse([
                'query' => $query,
                'results' => $suggestions,
                'debug' => $debug
            ]);

            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

            return $response;
        } catch (\Exception $e) {
            error_log("Autocomplete Error: " . $e->getMessage());

            $response = new JsonResponse([
                'error' => 'An error occurred while fetching suggestions',
                'debug' => $debug
            ], 500);

            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

            return $response;
        }
    }
}

?>