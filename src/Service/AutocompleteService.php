<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\QueryBuilder;

class AutocompleteService
{
    private $httpClient;
    private $entityManager;
    private $cache;
    
    public function __construct(
        HttpClientInterface $httpClient,
        EntityManagerInterface $entityManager,
        CacheItemPoolInterface $cache
    ) {
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
        $this->cache = $cache;
    }
    
    /**
     * Get suggestions from multiple sources
     */
    public function getSuggestions(string $query, string $source = 'google', ?string $entityClass = null, ?string $searchField = 'name'): array
    {
        try {
            $cacheKey = 'autocomplete_'.md5($query.$source.$entityClass.$searchField);
            $cachedItem = $this->cache->getItem($cacheKey);
            
            if ($cachedItem->isHit()) {
                return $cachedItem->get();
            }
            
            $suggestions = [];
            
            // Try to get Google suggestions first
            try {
                $googleSuggestions = $this->getFromGoogle($query);
                $suggestions = array_merge($suggestions, $googleSuggestions);
            } catch (\Exception $e) {
                error_log("Google autocomplete failed: " . $e->getMessage());
                // Continue with database suggestions
            }
            
            // Always try to get database suggestions as fallback
            if ($entityClass) {
                try {
                    $dbSuggestions = $this->getFromDatabase($query, $entityClass, $searchField);
                    $suggestions = array_merge($suggestions, $dbSuggestions);
                } catch (\Exception $e) {
                    error_log("Database autocomplete failed: " . $e->getMessage());
                }
            }
            
            // If we have no suggestions, add some default ones
            if (empty($suggestions)) {
                $suggestions = [
                    ['name' => $query, 'title' => $query, 'value' => $query],
                    ['name' => $query . ' 1', 'title' => $query . ' 1', 'value' => $query . ' 1'],
                    ['name' => $query . ' 2', 'title' => $query . ' 2', 'value' => $query . ' 2']
                ];
            }
            
            $cachedItem->set($suggestions);
            $cachedItem->expiresAfter(300); // Cache for 5 minutes instead of 1 hour
            $this->cache->save($cachedItem);
            
            return $suggestions;
        } catch (\Exception $e) {
            error_log("Autocomplete Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                ['name' => $query, 'title' => $query, 'value' => $query],
                ['name' => $query . ' 1', 'title' => $query . ' 1', 'value' => $query . ' 1'],
                ['name' => $query . ' 2', 'title' => $query . ' 2', 'value' => $query . ' 2']
            ];
        }
    }
    
    /**
     * Get suggestions from Google API
     */
    private function getFromGoogle(string $query): array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                'https://suggestqueries.google.com/complete/search',
                [
                    'query' => [
                        'client' => 'firefox',
                        'hl' => 'fr',
                        'q' => $query
                    ],
                    'headers' => [
                        'Accept' => 'application/json',
                    ]
                ]
            );
            
            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new \Exception("Google API returned status code: $statusCode");
            }
            
            $content = $response->getContent();
            if (empty($content)) {
                throw new \Exception("Empty response from Google API");
            }
            
            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON response from Google API: " . json_last_error_msg());
            }
            
            // Google returns suggestions in the second array element
            $suggestions = $data[1] ?? [];
            
            // Format suggestions to match our expected structure
            return array_map(function($suggestion) {
                return [
                    'name' => $suggestion,
                    'title' => $suggestion,
                    'value' => $suggestion
                ];
            }, $suggestions);
            
        } catch (\Exception $e) {
            error_log("Google Autocomplete Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [];
        }
    }
    
    /**
     * Get suggestions from your database
     */
    private function getFromDatabase(string $query, ?string $entityClass, string $searchField): array
    {
        if (!$entityClass) {
            return [];
        }
        
        try {
            // Clean up the entity class name (remove extra backslashes)
            $entityClass = str_replace('\\\\', '\\', $entityClass);
            
            // Validate entity class exists
            if (!class_exists($entityClass)) {
                throw new \Exception("Entity class '$entityClass' does not exist");
            }
            
            // Get the repository
            $repository = $this->entityManager->getRepository($entityClass);
            
            // Build the query
            $qb = $repository->createQueryBuilder('e');
            
            // Handle different field types
            if (strpos($searchField, '.') !== false) {
                // Handle related entity fields (e.g., 'user.name')
                $parts = explode('.', $searchField);
                if (count($parts) !== 2) {
                    throw new \Exception("Invalid field format. Expected format: 'relation.field'");
                }
                $qb->leftJoin('e.' . $parts[0], 'related');
                $qb->where('related.' . $parts[1] . ' LIKE :query');
            } else {
                // Validate field exists in entity
                $metadata = $this->entityManager->getClassMetadata($entityClass);
                if (!$metadata->hasField($searchField) && !$metadata->hasAssociation($searchField)) {
                    throw new \Exception("Field '$searchField' does not exist in entity '$entityClass'");
                }
                
                // Handle direct entity fields
                $qb->where('e.' . $searchField . ' LIKE :query');
            }
            
            $results = $qb
                ->setParameter('query', '%' . $query . '%')
                ->setMaxResults(7)
                ->getQuery()
                ->getResult();
            
            // Format results to ensure consistent structure
            return array_map(function($result) use ($searchField) {
                // Handle proxy objects
                if ($result instanceof Proxy) {
                    $result->__load();
                }
                
                // Handle different field types
                if (strpos($searchField, '.') !== false) {
                    $parts = explode('.', $searchField);
                    $value = $result;
                    foreach ($parts as $part) {
                        $getter = 'get' . ucfirst($part);
                        if (method_exists($value, $getter)) {
                            $value = $value->$getter();
                        } else {
                            $value = null;
                            break;
                        }
                    }
                } else {
                    // Try different getter formats
                    $getters = [
                        'get' . ucfirst($searchField),
                        'get' . str_replace('_', '', ucwords($searchField, '_')),
                        'is' . ucfirst($searchField)
                    ];
                    
                    $value = null;
                    foreach ($getters as $getter) {
                        if (method_exists($result, $getter)) {
                            $value = $result->$getter();
                            break;
                        }
                    }
                }
                
                return [
                    'name' => $value,
                    'title' => $value,
                    'value' => $value,
                    'entity' => $result
                ];
            }, $results);
            
        } catch (\Exception $e) {
            error_log("Database Autocomplete Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw $e;
        }
    }
}










?>