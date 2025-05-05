<?php

namespace App\Controller;

use App\Entity\Vol;
use App\Form\VolType;
use App\Service\GeocodingService;
use App\Service\FlightTrackingService;
use App\Repository\VolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[Route('/back/vol', name: 'app_back_vol_')]
final class BackVolController extends AbstractController
{
    private FlightTrackingService $flightTrackingService;
    private GeocodingService $geocodingService;

    public function __construct(FlightTrackingService $flightTrackingService, GeocodingService $geocodingService)
    {
        $this->flightTrackingService = $flightTrackingService;
        $this->geocodingService = $geocodingService;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(VolRepository $volRepository, EntityManagerInterface $em): Response
    {
        $vols = $volRepository->findAll();

        // Get averages from raw SQL
        $conn = $em->getConnection();
        $sql = 'SELECT 
                    AVG(f.FlightPrice) AS avgPrice,
                    AVG(f.AvailableSeats) AS avgSeats,
                    AVG(f.ClasseChaise) AS avgClasse
                FROM vol f';
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery()->fetchAssociative();

        return $this->render('back_vol/TableVol.html.twig', [
            'vols' => $vols,
            'averages' => $result,
        ]);
    }

    #[Route('/flights', name: 'flights', methods: ['GET'])]
    public function realTimeFlights(): Response
    {
        $flights = $this->flightTrackingService->getAircraftData();

        return $this->render('back_vol/TableVol.html.twig', [
            'flights' => $flights,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $vol = new Vol();
        $form = $this->createForm(VolType::class, $vol);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($vol);
            $entityManager->flush();
            $this->addFlash('success', 'Vol created successfully!');
            return $this->redirectToRoute('app_back_vol_index');
        }

        return $this->render('back_vol/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Vol $vol): Response
    {
        return $this->render('back_vol/show.html.twig', [
            'vol' => $vol,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Vol $vol, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VolType::class, $vol);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Vol updated successfully!');
            return $this->redirectToRoute('app_back_vol_index');
        }

        return $this->render('back_vol/edit.html.twig', [
            'vol' => $vol,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Vol $vol, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $vol->getId(), $request->request->get('_token'))) {
            $entityManager->remove($vol);
            $entityManager->flush();
            $this->addFlash('success', 'Vol deleted successfully!');
        }

        return $this->redirectToRoute('app_back_vol_index');
    }

    #[Route('/export/pdf', name: 'export_pdf', methods: ['GET'])]
    public function exportPdf(VolRepository $volRepository): Response
    {
        // Fetch all flights for export
        $vols = $volRepository->findAll();

        // Render the data into HTML (this is the content for PDF)
        $html = $this->renderView('back_vol/pdfExport.html.twig', [
            'vols' => $vols,
        ]);

        // Initialize DomPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true); // Enables HTML5 parser (optional)
        $options->set('isPhpEnabled', true); // Enables PHP in templates (optional)
        $dompdf = new Dompdf($options);

        // Load HTML content
        $dompdf->loadHtml($html);

        // (Optional) Set paper size
        $dompdf->setPaper('A4', 'portrait');

        // Render PDF (first pass)
        $dompdf->render();

        // Stream the file or force download
        $response = new Response($dompdf->stream('vols_list.pdf', ['Attachment' => 1]));

        // Set the correct headers for download
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="vols_list.pdf"');

        return $response;
    }

}
