<?php

namespace App\Controller;

use App\Entity\Contrat;
use App\Entity\Vehicule;
use App\Form\ContratType;
use App\Repository\ContratRepository;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/back/contrat')]
class BackContratController extends AbstractController
{
    private $slugger;
    private $uploadsDirectory;
    private $validator;
    private $logger;

    public function __construct(
        SluggerInterface $slugger,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        string $uploadsDirectory = 'uploads/permits'
    ) {
        $this->slugger = $slugger;
        $this->uploadsDirectory = $uploadsDirectory;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    #[Route('/', name: 'app_back_contrat', methods: ['GET'])]
    public function index(ContratRepository $contratRepository, VehiculeRepository $vehiculeRepository): Response
    {
        $addForm = $this->createForm(ContratType::class, new Contrat());
        $editForm = $this->createForm(ContratType::class, new Contrat(), [
            'action' => $this->generateUrl('app_back_contrat_edit', ['id_location' => 0])
        ]);

        return $this->render('back_contrat/TableContrat.html.twig', [
            'contrats' => $contratRepository->findAll(),
            'vehicules' => $vehiculeRepository->findAll(),
            'form' => $addForm->createView(),
            'edit_form' => $editForm->createView(),
        ]);
    }

    #[Route('/new', name: 'app_back_contrat_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $contrat = new Contrat();
            $form = $this->createForm(ContratType::class, $contrat);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // Handle photo upload
                $photoFile = $form->get('photo_permit')->getData();
                if ($photoFile) {
                    $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $this->slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                    try {
                        $photoFile->move(
                            $this->getParameter('permits_directory'),
                            $newFilename
                        );
                        $contrat->setPhotoPermit($newFilename);
                    } catch (FileException $e) {
                        throw new \Exception('Erreur lors du téléversement du fichier : ' . $e->getMessage());
                    }
                }

                $entityManager->persist($contrat);
                $entityManager->flush();

                return new JsonResponse([
                    'status' => 'success',
                    'message' => 'Contrat créé avec succès.',
                    'data' => [
                        'id_location' => $contrat->getIdLocation(),
                        'dateD' => $contrat->getDateD(),
                        'dateF' => $contrat->getDateF(),
                        'cinlocateur' => $contrat->getCinLocateur(),
                        'photo_permit' => $contrat->getPhotoPermit(),
                        'photo_url' => $contrat->getPhotoPermit() ? '/uploads/permits/' . $contrat->getPhotoPermit() : null,
                        'idVehicule' => [
                            'id_vehicule' => $contrat->getIdVehicule()->getId_vehicule(),
                            'matricule' => $contrat->getIdVehicule()->getMatricule()
                        ]
                    ]
                ]);
            }

            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur dans le formulaire : ' . implode(', ', $errors)
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id_location}/edit', name: 'app_back_contrat_edit', methods: ['POST'])]
    public function edit(Request $request, int $id_location, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $contrat = $entityManager->getRepository(Contrat::class)->find($id_location);
            if (!$contrat) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Contrat introuvable.'
                ], 404);
            }

            $form = $this->createForm(ContratType::class, $contrat);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // Handle photo upload
                $photoFile = $form->get('photo_permit')->getData();
                if ($photoFile) {
                    // Delete old photo if exists
                    if ($contrat->getPhotoPermit()) {
                        $oldFile = $this->getParameter('photos_directory') . '/' . $contrat->getPhotoPermit();
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }

                    $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $this->slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                    try {
                        $photoFile->move(
                            $this->getParameter('photos_directory'),
                            $newFilename
                        );
                        $contrat->setPhotoPermit($newFilename);
                    } catch (FileException $e) {
                        throw new \Exception('Erreur lors du téléversement du fichier : ' . $e->getMessage());
                    }
                }

                $entityManager->flush();

                return new JsonResponse([
                    'status' => 'success',
                    'message' => 'Contrat modifié avec succès.',
                    'data' => [
                        'id_location' => $contrat->getIdLocation(),
                        'dateD' => $contrat->getDateD(),
                        'dateF' => $contrat->getDateF(),
                        'cinlocateur' => $contrat->getCinLocateur(),
                        'photo_permit' => $contrat->getPhotoPermit(),
                        'photo_url' => $contrat->getPhotoPermit() ? '/uploads/permits/' . $contrat->getPhotoPermit() : null,
                        'idVehicule' => [
                            'id_vehicule' => $contrat->getIdVehicule()->getId_vehicule(),
                            'matricule' => $contrat->getIdVehicule()->getMatricule()
                        ]
                    ]
                ]);
            }

            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur dans le formulaire : ' . implode(', ', $errors)
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/delete/{id_location}', name: 'app_back_contrat_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager, int $id_location): JsonResponse
    {
        $connection = $entityManager->getConnection();

        try {
            $this->logger->info("Attempting to delete contrat with id_location: {$id_location}");

            // Start transaction
            $connection->beginTransaction();

            // Find the contract
            $contrat = $entityManager->getRepository(Contrat::class)->find($id_location);

            if (!$contrat) {
                throw new \Exception('Contrat non trouvé.');
            }

            // Delete photo if exists
            if ($contrat->getPhotoPermit()) {
                $photoPath = $this->getParameter('kernel.project_dir') . '/public/uploads/permits/' . $contrat->getPhotoPermit();
                if (file_exists($photoPath)) {
                    if (!unlink($photoPath)) {
                        throw new \Exception('Erreur lors de la suppression du fichier photo.');
                    }
                }
            }

            // Delete using DQL and check affected rows
            $query = $entityManager->createQuery('DELETE FROM App\Entity\Contrat c WHERE c.id_location = :id');
            $query->setParameter('id', $id_location);
            $sql = $query->getSQL();
            $this->logger->info("Executing DQL: {$sql} with id: {$id_location}");
            $affectedRows = $query->execute();

            // Verify that exactly one row was deleted
            if ($affectedRows !== 1) {
                throw new \Exception('Aucun contrat n\'a été supprimé. Vérifiez les contraintes de la base de données.');
            }

            // Commit transaction
            $connection->commit();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Contrat supprimé avec succès.',
                'id' => $id_location
            ]);

        } catch (\Doctrine\DBAL\Exception\ConstraintViolationException $e) {
            // Handle specific database constraint errors
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $this->logger->error("Constraint violation during deletion of contrat {$id_location}: {$e->getMessage()}");
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Impossible de supprimer le contrat en raison de contraintes de la base de données.'
            ], 400);
        } catch (\Exception $e) {
            // Rollback transaction
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $this->logger->error("Error during deletion of contrat {$id_location}: {$e->getMessage()}");
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }
}