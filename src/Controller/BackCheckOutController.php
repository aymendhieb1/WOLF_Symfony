<?php

namespace App\Controller;

use App\Entity\CheckoutVol;
use App\Entity\Vol; // Import the Vol entity
use App\Form\CheckoutVolType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\CurrencyService;


#[Route('/back/checkoutvol')]
final class BackCheckoutController extends AbstractController
{
private CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }
    #[Route('/list', name: 'app_back_checkoutvol_list', methods: ['GET'])]
    public function list(EntityManagerInterface $entityManager): Response
    {
        $checkouts = $entityManager->getRepository(CheckoutVol::class)->findAll();
        $rates = $this->currencyService->getExchangeRates();

        // Calculate averages
        $totalPrice = 0;
        $totalPassengers = 0;
        $totalCrew = 0;
        $count = count($checkouts);

        foreach ($checkouts as $checkout) {
            $totalPrice += $checkout->getTotalPrice();
            $totalPassengers += $checkout->getTotalPassengers();
            $totalCrew += $checkout->getFlightCrew();
        }

        $averages = [
            'avgPrice' => $count > 0 ? $totalPrice / $count : 0,
            'avgPassengers' => $count > 0 ? $totalPassengers / $count : 0,
            'avgCrew' => $count > 0 ? $totalCrew / $count : 0
        ];

        return $this->render('back_check_out/TableCheckOut.html.twig', [
            'checkouts' => $checkouts,
            'rates' => $rates,
            'averages' => $averages
        ]);
    }

    #[Route('/create', name: 'app_back_checkoutvol_create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $checkout = new CheckoutVol();

        // Generate non-editable fields
        $checkout->setFlightCrew(rand(4, 20));

        $aircraftModels = ['Boeing 747', 'Airbus A320', 'Boeing 777', 'Airbus A380'];
        $aircraft = $aircraftModels[array_rand($aircraftModels)];
        $checkout->setAircraft($aircraft);

        $pricePerPassenger = $this->getTicketPriceByAircraft($aircraft);

        $gateLetter = chr(rand(65, 70)); // Gates A-F
        $gateNumber = rand(1, 30);
        $checkout->setGate($gateLetter . $gateNumber);

        $date = new \DateTime();
        $date->modify('+1 day');
        $checkout->setReservationDate($date);

        // Default value that can be changed
        $checkout->setTotalPassengers(rand(1, 10));

        $statusOptions = ['Confirmee', 'En Attente', 'Annulee'];
        $checkout->setReservationStatus($statusOptions[array_rand($statusOptions)]);

        $checkout->setTotalPrice(0); // Will be calculated

        $form = $this->createForm(CheckoutVolType::class, $checkout);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Fetch the FlightID from the form input
            $flightID = $checkout->getFlightID();

            // Fetch the flight from the DB to validate if the FlightID exists
            $flight = $entityManager->getRepository(Vol::class)->find($flightID);

            if (!$flight) {
                // If no flight with that ID exists, show an error message and redirect back to the form
                $this->addFlash('error', 'Flight ID does not exist.');
                return $this->redirectToRoute('app_back_checkoutvol_create');
            }

            // Calculate price based on user-edited passenger count
            $checkout->setTotalPrice($checkout->getTotalPassengers() * $pricePerPassenger);

            // Persist the CheckoutVol entity
            $entityManager->persist($checkout);
            $entityManager->flush();

            return $this->redirectToRoute('app_back_checkoutvol_list');
        }

        return $this->render('back_check_out/CreateCheckOut.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function getTicketPriceByAircraft(string $aircraft): int
    {
        return match ($aircraft) {
            'Boeing 747' => rand(600, 1200),
            'Airbus A320' => rand(100, 400),
            'Boeing 777' => rand(600, 1000),
            'Airbus A380' => rand(700, 1100),
            default => rand(200, 500)
        };
    }

    #[Route('/edit/{id}', name: 'app_back_checkoutvol_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $checkout = $entityManager->getRepository(CheckoutVol::class)->find($id);

        if (!$checkout) {
            throw $this->createNotFoundException('CheckoutVol not found.');
        }

        $form = $this->createForm(CheckoutVolType::class, $checkout);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if the FlightID exists in the Vol table
            $flightID = $checkout->getFlightID();
            $flight = $entityManager->getRepository(Vol::class)->find($flightID);

            if (!$flight) {
                // If the FlightID does not exist, show an error message
                $this->addFlash('error', 'The Flight ID does not exist.');
                return $this->redirectToRoute('app_back_checkoutvol_edit', ['id' => $id]);
            }

            // Calculate price per passenger and total price
            $pricePerPassenger = $this->getTicketPriceByAircraft($checkout->getAircraft());
            $checkout->setTotalPrice($checkout->getTotalPassengers() * $pricePerPassenger);

            // Persist the updated CheckoutVol entity
            $entityManager->flush();

            return $this->redirectToRoute('app_back_checkoutvol_list');
        }

        return $this->render('back_check_out/EditCheckOut.html.twig', [
            'form' => $form->createView(),
            'checkout' => $checkout,
        ]);
    }


    #[Route('/delete/{id}', name: 'app_back_checkoutvol_delete', methods: ['POST'])]
    public function delete(int $id, EntityManagerInterface $entityManager): Response
    {
        $checkout = $entityManager->getRepository(CheckoutVol::class)->find($id);

        if ($checkout) {
            $entityManager->remove($checkout);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_back_checkoutvol_list');
    }
}
