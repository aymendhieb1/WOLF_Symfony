<?php

namespace App\Controller;

use App\Entity\Hotel;
use App\Form\HotelType;
use App\Repository\HotelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;


use Knp\Snappy\Pdf;

#[Route('/back/hotel')]
class BackHotelController extends AbstractController
{
    #[Route('/', name: 'app_back_hotel_index', methods: ['GET'])]
    public function index(HotelRepository $hotelRepository): Response
    {
        return $this->render('back_hotel/TableHotel.html.twig', [
            'hotels' => $hotelRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_back_hotel_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, MailerInterface $mailer, Pdf $knpSnappy): Response
    {
        $hotel = new Hotel();
        $form = $this->createForm(HotelType::class, $hotel);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
    
                try {
                    $uploadDir = $this->getParameter('hotel_images_directory');
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $imageFile->move(
                        $uploadDir,
                        $newFilename
                    );
                    
                    $hotel->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image');
                }
            }
    
            $entityManager->persist($hotel);
            $entityManager->flush();
    
            // Generate PDF
            $html = $this->renderView('back_hotel/pdf.html.twig', [
                'hotel' => $hotel,
            ]);
            
            $pdfContent = $knpSnappy->getOutputFromHtml($html, [
                'disable-smart-shrinking' => true,
                'no-stop-slow-scripts' => true,
                'enable-local-file-access' => true,
            ]);
    
            // Save PDF to a temporary location
            $pdfFilename = 'hotel_' . $hotel->getId() . '.pdf';
            $tempDir = sys_get_temp_dir(); // Use system temp directory
            file_put_contents($tempDir . '/' . $pdfFilename, $pdfContent);
    
            // Send email with the PDF attachment
            $email = (new Email())
                ->from('triptogo2025@gmail.com')
                ->to($form->get('email')->getData())
                ->subject('Votre hôtel a été ajouté avec succès !')
                ->text('Votre hôtel a été ajouté avec succès. Veuillez trouver ci-joint le PDF.')
                ->attachFromPath($tempDir . '/' . $pdfFilename) // Attach the PDF
                ->html('
                <div style="font-family: Arial, sans-serif; background-color: #f4f7fa; padding: 30px;">
                    <div style="max-width: 600px; margin: auto; background-color: #ffffff; border-radius: 10px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <img src="cid:logo" alt="TripToGo Logo" style="width: 120px; height: auto;">
                        </div>
                        <h2 style="color: #333333; text-align: center;">Confirmation d\'ajout d\'hôtel</h2>
                        <p style="font-size: 16px; color: #555555; text-align: center;">
                            Bonjour, <br><br>
                            Votre hôtel a été ajouté avec succès sur TripToGo. Voici les détails :
                        </p>
                        <div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0;">
                            <h3 style="color: #ff681a; margin-bottom: 15px;">Détails de l\'hôtel</h3>
                            <p style="margin: 8px 0;"><strong>Nom:</strong> ' . $hotel->getNom() . '</p>
                            <p style="margin: 8px 0;"><strong>Adresse:</strong> ' . $hotel->getLocalisation() . '</p>
                            <p style="margin: 8px 0;"><strong>Email:</strong> ' . $hotel->getEmail() . '</p>
                            <p style="margin: 8px 0;"><strong>Téléphone:</strong> ' . $hotel->getTelephone() . '</p>
                            <p style="margin: 8px 0;"><strong>Description:</strong> ' . $hotel->getDescription() . '</p>
                        </div>
                        <p style="font-size: 16px; color: #555555; text-align: center;">
                            Vous trouverez ci-joint un PDF contenant tous les détails de votre hôtel.
                        </p>
                        <hr style="border: none; border-top: 1px solid #eeeeee; margin: 30px 0;">
                        <p style="font-size: 12px; color: #aaaaaa; text-align: center;">
                            © ' . date("Y") . ' TripToGo. Tous droits réservés.
                        </p>
                    </div>
                </div>')
                ->embedFromPath('C:/xampp/htdocs/Projet_v2/TripToGo/public/assets/img/primary.png', 'logo');
            $mailer->send($email);
    
            return $this->redirectToRoute('app_back_hotel_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('back_hotel/new.html.twig', [
            'hotel' => $hotel,
            'form' => $form->createView(),
        ]);
    }
  
    #[Route('/{id}/edit', name: 'app_back_hotel_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $hotel = $entityManager->getRepository(Hotel::class)->find($id);
        
        if (!$hotel) {
            throw $this->createNotFoundException('Hotel not found');
        }

        $form = $this->createForm(HotelType::class, $hotel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $uploadDir = $this->getParameter('hotel_images_directory');
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    // Remove old image if it exists
                    $oldImage = $hotel->getImage();
                    if ($oldImage) {
                        $oldImagePath = $uploadDir . '/' . $oldImage;
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    
                    $imageFile->move(
                        $uploadDir,
                        $newFilename
                    );
                    
                    $hotel->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image');
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_back_hotel_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back_hotel/edit.html.twig', [
            'hotel' => $hotel,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_back_hotel_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, EntityManagerInterface $entityManager): Response
    {
        $hotel = $entityManager->getRepository(Hotel::class)->find($id);
        
        if (!$hotel) {
            throw $this->createNotFoundException('Hotel not found');
        }

        if ($this->isCsrfTokenValid('delete'.$hotel->getId(), $request->request->get('_token'))) {
            $entityManager->remove($hotel);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_back_hotel_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/{id}', name: 'app_back_hotel_pdf', methods: ['GET', 'POST'])]
    public function extractPdf(Request $request, int $id, EntityManagerInterface $entityManager, Pdf $knpSnappy): Response
    {
        // Retrieve the hotel entity from the database
        $hotel = $entityManager->getRepository(Hotel::class)->find($id);
        
        if (!$hotel) {
            throw $this->createNotFoundException('Hotel not found');
        }
    
        // Render the view to a variable
        $html = $this->renderView('back_hotel/pdf.html.twig', [
            'hotel' => $hotel,
        ]);
    
        // Generate PDF from HTML with proper options
        $pdfContent = $knpSnappy->getOutputFromHtml($html, [
            'disable-smart-shrinking' => true,
            'no-stop-slow-scripts' => true,
            'enable-local-file-access' => true,
        ]);
    
        // Create a Response with the PDF content
        return new Response(
            $pdfContent,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="hotel_'.$hotel->getId().'.pdf"'
            ]
        );
    }
}
