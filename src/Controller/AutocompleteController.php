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
        
        // Debug information
        $debug = [
            'query' => $query,
            'source' => $source,
            'entity' => $entity,
            'searchField' => $searchField,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ];
        
        try {
            // Only use database if explicitly requested and entity is provided
            if ($source === 'database' && !$entity) {
                $source = 'google'; // Fallback to google if database requested without entity
            }
            
            $suggestions = $this->autocompleteService->getSuggestions($query, $source, $entity, $searchField);
            
            $response = new JsonResponse([
                'query' => $query,
                'results' => $suggestions,
                'debug' => $debug
            ]);
            
            // Add CORS headers
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
            
            // Add CORS headers even for error responses
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
            
            return $response;
        }
    }
}

?>