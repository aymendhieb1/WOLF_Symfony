<?php

namespace App\Controller;

use App\Entity\Vol;
use App\Repository\VolRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/vol')]
class VolController extends AbstractController
{
    #[Route('/', name: 'app_vol_index', methods: ['GET'])]
    public function index(Request $request, VolRepository $volRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $search = $request->query->get('search', '');
        $departure = $request->query->get('departure', '');
        $destination = $request->query->get('destination', '');
        $minPrice = $request->query->get('min_price') !== '' ? (int)$request->query->get('min_price') : null;
        $maxPrice = $request->query->get('max_price') !== '' ? (int)$request->query->get('max_price') : null;
        $departureTime = $request->query->get('departure_time', '');
        
        // Get all flights by default
        $allVols = $volRepository->findAll();
        
        // Only apply filters if search form was submitted
        if ($request->query->has('search') || $request->query->has('departure') || 
            $request->query->has('destination') || $request->query->has('min_price') || 
            $request->query->has('max_price') || $request->query->has('departure_time')) {
            
            $criteria = [];
            if ($departure) {
                $criteria['depart'] = $departure;
            }
            if ($destination) {
                $criteria['destination'] = $destination;
            }
            
            // Get filtered flights
            if (!empty($criteria)) {
                $allVols = $volRepository->findFlightsByCriteria($criteria);
            }
            
            // Apply search filter if provided
            if ($search) {
                $allVols = array_filter($allVols, function($vol) use ($search) {
                    return stripos($vol->getDepart(), $search) !== false ||
                           stripos($vol->getDestination(), $search) !== false ||
                           stripos($vol->getCompagnie(), $search) !== false;
                });
            }
            
            // Apply price filters if provided
            if ($minPrice !== null || $maxPrice !== null) {
                $allVols = array_filter($allVols, function($vol) use ($minPrice, $maxPrice) {
                    $price = (int)$vol->getPrix();
                    
                    if ($minPrice !== null && $maxPrice !== null) {
                        return $price >= $minPrice && $price <= $maxPrice;
                    } elseif ($minPrice !== null) {
                        return $price >= $minPrice;
                    } else {
                        return $price <= $maxPrice;
                    }
                });
            }
            
            // Apply time filter if provided
            if ($departureTime) {
                $allVols = array_filter($allVols, function($vol) use ($departureTime) {
                    $flightTime = $vol->getHeureDepart()->format('H:i');
                    return $flightTime >= $departureTime;
                });
            }
        }
        
        // Calculate pagination
        $itemsPerPage = 6;
        $totalItems = count($allVols);
        $totalPages = max(1, ceil($totalItems / $itemsPerPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $itemsPerPage;
        
        // Get the current page's items
        $vols = array_slice($allVols, $offset, $itemsPerPage);

        return $this->render('vol/index.html.twig', [
            'vols' => $vols,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'departure' => $departure,
            'destination' => $destination,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'departureTime' => $departureTime,
            'hasSearchParams' => $request->query->has('search') || $request->query->has('departure') || 
                               $request->query->has('destination') || $request->query->has('min_price') || 
                               $request->query->has('max_price') || $request->query->has('departure_time')
        ]);
    }

    #[Route('/{id}', name: 'app_vol_show', methods: ['GET'])]
    public function show(Vol $vol): Response
    {
        return $this->render('vol/show_with_weather.html.twig', [
            'vol' => $vol
        ]);
    }
} 