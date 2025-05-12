<?php

namespace App\Controller;

use App\Entity\ResVol;
use App\Entity\Vol;
use App\Form\ResVolType;
use App\Repository\ResVolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/resvol')]
class ResVolController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/new/{id}', name: 'app_resvol_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Vol $vol, EntityManagerInterface $entityManager): Response
    {
        $resVol = new ResVol();
        
        // Pré-remplir les données du vol
        $resVol->setVol($vol);
        $resVol->setDeparture($vol->getDepart());
        $resVol->setDestination($vol->getDestination());
        $resVol->setDepartureTime($vol->getHeureDepart());
        $resVol->setArrivalTime($vol->getHeureArrivee());
        $resVol->setClasseChaise($vol->getClasseChaise());
        $resVol->setFlightPrice($vol->getPrix());
        $resVol->setAirline($vol->getCompagnie());
        $resVol->setVolReservationDate(new \DateTime());
        
        // Récupérer l'utilisateur connecté
        $user = $this->security->getUser();
        if ($user) {
            $resVol->setUser($user);
        }

        $form = $this->createForm(ResVolType::class, $resVol);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Instead of saving, redirect to payment page
            return $this->redirectToRoute('app_payment_vol', [
                'volId' => $vol->getId(),
                'amount' => $vol->getPrix()
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('resvol/new.html.twig', [
            'res_vol' => $resVol,
            'form' => $form->createView(),
            'vol' => $vol
        ]);
    }

    #[Route('/back', name: 'app_back_resvol_index', methods: ['GET'])]
    public function index(Request $request, ResVolRepository $resVolRepository): Response
    {
        // Get search parameters (only used for initial filtering if needed)
        $search = $request->query->get('search', '');

        // Get all reservations
        $queryBuilder = $resVolRepository->createQueryBuilder('r')
            ->leftJoin('r.user', 'u');

        if ($search) {
            $queryBuilder->where('r.departure LIKE :search')
                ->orWhere('r.destination LIKE :search')
                ->orWhere('r.airline LIKE :search')
                ->orWhere('r.classeChaise LIKE :search')
                ->orWhere('u.nom LIKE :search')
                ->orWhere('u.prenom LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Get all results without pagination
        $reservations = $queryBuilder->getQuery()->getResult();

        // Calculate statistics
        $destinationStats = [];
        $airlineStats = [];
        $priceStats = [];
        $monthlyStats = [];

        foreach ($reservations as $res) {
            // Destination stats
            $destination = $res->getDestination();
            if (!isset($destinationStats[$destination])) {
                $destinationStats[$destination] = 0;
            }
            $destinationStats[$destination]++;

            // Airline stats
            $airline = $res->getAirline();
            if (!isset($airlineStats[$airline])) {
                $airlineStats[$airline] = 0;
            }
            $airlineStats[$airline]++;

            // Price stats
            $price = $res->getFlightPrice();
            if (!isset($priceStats[$price])) {
                $priceStats[$price] = 0;
            }
            $priceStats[$price]++;

            // Monthly stats
            $month = $res->getVolReservationDate()->format('F Y');
            if (!isset($monthlyStats[$month])) {
                $monthlyStats[$month] = 0;
            }
            $monthlyStats[$month]++;
        }

        // Sort all stats
        arsort($destinationStats);
        arsort($airlineStats);
        arsort($priceStats);

        return $this->render('back_resvol/TableResVol.html.twig', [
            'res_vols' => $reservations, // Pass all reservations
            'search' => $search,
            'monthly_labels' => array_keys($monthlyStats),
            'monthly_data' => array_values($monthlyStats),
            'destination_labels' => array_keys($destinationStats),
            'destination_data' => array_values($destinationStats),
            'airline_labels' => array_keys($airlineStats),
            'airline_data' => array_values($airlineStats),
            'price_labels' => array_keys($priceStats),
            'price_data' => array_values($priceStats),
        ]);
    }

    #[Route('/back/{id}/edit', name: 'app_back_resvol_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ResVol $resVol, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ResVolType::class, $resVol);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La réservation a été modifiée avec succès.');
            return $this->redirectToRoute('app_back_resvol_index');
        }

        return $this->render('back_resvol/edit.html.twig', [
            'res_vol' => $resVol,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/back/{id}', name: 'app_back_resvol_delete', methods: ['POST'])]
    public function delete(Request $request, ResVol $resVol, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$resVol->getId(), $request->request->get('_token'))) {
            $entityManager->remove($resVol);
            $entityManager->flush();
            $this->addFlash('success', 'La réservation a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_back_resvol_index');
    }
} 