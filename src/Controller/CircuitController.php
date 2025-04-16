<?php

namespace App\Controller;

use App\Entity\Activite;
use App\Repository\ActiviteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

class CircuitController extends AbstractController
{
    #[Route('/circuit', name: 'app_circuit')]
    public function index(Request $request, ActiviteRepository $activiteRepository, PaginatorInterface $paginator): Response
    {
        // Get all activities
        $query = $activiteRepository->createQueryBuilder('a')
            ->orderBy('a.nom', 'ASC')
            ->getQuery();

        // Paginate the results
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            9 // Items per page
        );

        // Get min and max prices for the price slider
        $minPrice = $activiteRepository->createQueryBuilder('a')
            ->select('MIN(a.prix)')
            ->getQuery()
            ->getSingleScalarResult();

        $maxPrice = $activiteRepository->createQueryBuilder('a')
            ->select('MAX(a.prix)')
            ->getQuery()
            ->getSingleScalarResult();

        // Define months for the month filter
        $months = [
            ['value' => '01', 'name' => 'Janvier'],
            ['value' => '02', 'name' => 'Février'],
            ['value' => '03', 'name' => 'Mars'],
            ['value' => '04', 'name' => 'Avril'],
            ['value' => '05', 'name' => 'Mai'],
            ['value' => '06', 'name' => 'Juin'],
            ['value' => '07', 'name' => 'Juillet'],
            ['value' => '08', 'name' => 'Août'],
            ['value' => '09', 'name' => 'Septembre'],
            ['value' => '10', 'name' => 'Octobre'],
            ['value' => '11', 'name' => 'Novembre'],
            ['value' => '12', 'name' => 'Décembre'],
        ];

        // Define activity types for the type filter
        $activityTypes = [
            ['value' => 'sport', 'name' => 'Sport'],
            ['value' => 'culture', 'name' => 'Culture'],
            ['value' => 'nature', 'name' => 'Nature'],
            ['value' => 'aventure', 'name' => 'Aventure'],
        ];

        // Define filters for the filter section
        $filters = [
            ['id' => 'filter1', 'value' => 'popular', 'label' => 'Populaire'],
            ['id' => 'filter2', 'value' => 'new', 'label' => 'Nouveau'],
            ['id' => 'filter3', 'value' => 'featured', 'label' => 'En vedette'],
        ];

        return $this->render('activite/index.html.twig', [
            'activites' => $pagination,
            'totalPages' => ceil($pagination->getTotalItemCount() / 9),
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'months' => $months,
            'activityTypes' => $activityTypes,
            'filters' => $filters,
        ]);
    }

    #[Route('/circuit/search', name: 'app_circuit_search')]
    public function search(Request $request, ActiviteRepository $activiteRepository, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search');
        $location = $request->query->get('location');
        $month = $request->query->get('month');
        $type = $request->query->get('type');
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');
        $filter = $request->query->get('filter');

        $queryBuilder = $activiteRepository->createQueryBuilder('a');

        if ($search) {
            $queryBuilder->andWhere('a.nom LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($location) {
            $queryBuilder->andWhere('a.lieu LIKE :location')
                ->setParameter('location', '%' . $location . '%');
        }

        if ($month) {
            $queryBuilder->andWhere('MONTH(a.date) = :month')
                ->setParameter('month', $month);
        }

        if ($type) {
            $queryBuilder->andWhere('a.type = :type')
                ->setParameter('type', $type);
        }

        if ($minPrice && $maxPrice) {
            $queryBuilder->andWhere('a.prix BETWEEN :minPrice AND :maxPrice')
                ->setParameter('minPrice', $minPrice)
                ->setParameter('maxPrice', $maxPrice);
        }

        // Apply sorting based on filter
        if ($filter) {
            switch ($filter) {
                case 'popular':
                    $queryBuilder->orderBy('a.rating', 'DESC');
                    break;
                case 'new':
                    $queryBuilder->orderBy('a.date', 'DESC');
                    break;
                case 'featured':
                    $queryBuilder->orderBy('a.prix', 'DESC');
                    break;
            }
        }

        $query = $queryBuilder->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            9
        );

        // Get min and max prices for the price slider
        $minPrice = $activiteRepository->createQueryBuilder('a')
            ->select('MIN(a.prix)')
            ->getQuery()
            ->getSingleScalarResult();

        $maxPrice = $activiteRepository->createQueryBuilder('a')
            ->select('MAX(a.prix)')
            ->getQuery()
            ->getSingleScalarResult();

        // Define months for the month filter
        $months = [
            ['value' => '01', 'name' => 'Janvier'],
            ['value' => '02', 'name' => 'Février'],
            ['value' => '03', 'name' => 'Mars'],
            ['value' => '04', 'name' => 'Avril'],
            ['value' => '05', 'name' => 'Mai'],
            ['value' => '06', 'name' => 'Juin'],
            ['value' => '07', 'name' => 'Juillet'],
            ['value' => '08', 'name' => 'Août'],
            ['value' => '09', 'name' => 'Septembre'],
            ['value' => '10', 'name' => 'Octobre'],
            ['value' => '11', 'name' => 'Novembre'],
            ['value' => '12', 'name' => 'Décembre'],
        ];

        // Define activity types for the type filter
        $activityTypes = [
            ['value' => 'sport', 'name' => 'Sport'],
            ['value' => 'culture', 'name' => 'Culture'],
            ['value' => 'nature', 'name' => 'Nature'],
            ['value' => 'aventure', 'name' => 'Aventure'],
        ];

        // Define filters for the filter section
        $filters = [
            ['id' => 'filter1', 'value' => 'popular', 'label' => 'Populaire'],
            ['id' => 'filter2', 'value' => 'new', 'label' => 'Nouveau'],
            ['id' => 'filter3', 'value' => 'featured', 'label' => 'En vedette'],
        ];

        return $this->render('activite/index.html.twig', [
            'activites' => $pagination,
            'totalPages' => ceil($pagination->getTotalItemCount() / 9),
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'months' => $months,
            'activityTypes' => $activityTypes,
            'filters' => $filters,
        ]);
    }
} 