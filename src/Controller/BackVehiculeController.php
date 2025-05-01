<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Vehicule;
use App\Form\VehiculeType;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/back/vehicule')]
final class BackVehiculeController extends AbstractController
{
    #[Route('', name: 'app_back_vehicule', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        try {
            $vehicules = $entityManager->getRepository(Vehicule::class)->findAll();
            error_log('Loaded vehicles: ' . count($vehicules));
            return $this->render('back_vehicule/TableVehicule.html.twig', [
                'controller_name' => 'BackVehiculeController',
                'vehicules' => $vehicules,
            ]);
        } catch (\Exception $e) {
            error_log('Index error: ' . $e->getMessage());
            throw $e;
        }
    }

    #[Route('/new', name: 'app_back_vehicule_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        error_log('New vehicle request: ' . print_r($data, true));

        if (!$data || !is_array($data)) {
            error_log('Add error: Invalid JSON');
            return new JsonResponse(['status' => 'error', 'message' => 'Données JSON invalides.'], 400);
        }

        $vehicule = new Vehicule();
        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($vehicule);
                $entityManager->flush();
                error_log('Vehicle added, ID: ' . $vehicule->getId_vehicule());
                return new JsonResponse([
                    'status' => 'success',
                    'id' => $vehicule->getId_vehicule(),
                    'message' => 'Véhicule ajouté avec succès.',
                    'data' => [
                        'matricule' => $vehicule->getMatricule(),
                        'status' => $vehicule->getStatus(),
                        'nbPlace' => $vehicule->getNbPlace(),
                        'cylinder' => $vehicule->getCylinder(),
                    ],
                    'delete_url' => $this->generateUrl('app_back_vehicule_delete', ['id' => $vehicule->getId_vehicule()])
                ], 200);
            } catch (\Exception $e) {
                error_log('Add error: ' . $e->getMessage());
                return new JsonResponse(['status' => 'error', 'message' => 'Erreur serveur: ' . $e->getMessage()], 500);
            }
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        error_log('Add validation errors: ' . implode(', ', $errors));
        return new JsonResponse(['status' => 'error', 'message' => implode(', ', $errors) ?: 'Formulaire invalide.'], 422);
    }

    #[Route('/{id}/edit', name: 'app_back_vehicule_edit', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, ?Vehicule $vehicule, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$vehicule) {
            error_log('Edit error: Vehicle not found');
            return new JsonResponse(['status' => 'error', 'message' => 'Véhicule non trouvé.'], 404);
        }

        $data = json_decode($request->getContent(), true);
        error_log('Edit vehicle ID: ' . $vehicule->getId_vehicule() . ', Data: ' . print_r($data, true));

        if (!$data || !is_array($data)) {
            error_log('Edit error: Invalid JSON');
            return new JsonResponse(['status' => 'error', 'message' => 'Données JSON invalides.'], 400);
        }

        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                error_log('Vehicle edited, ID: ' . $vehicule->getId_vehicule());
                return new JsonResponse([
                    'status' => 'success',
                    'id' => $vehicule->getId_vehicule(),
                    'message' => 'Véhicule modifié avec succès.',
                    'data' => [
                        'matricule' => $vehicule->getMatricule(),
                        'status' => $vehicule->getStatus(),
                        'nbPlace' => $vehicule->getNbPlace(),
                        'cylinder' => $vehicule->getCylinder(),
                    ]
                ], 200);
            } catch (\Exception $e) {
                error_log('Edit error: ' . $e->getMessage());
                return new JsonResponse(['status' => 'error', 'message' => 'Erreur serveur: ' . $e->getMessage()], 500);
            }
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        error_log('Edit validation errors: ' . implode(', ', $errors));
        return new JsonResponse(['status' => 'error', 'message' => implode(', ', $errors) ?: 'Formulaire invalide.'], 422);
    }

    #[Route('/delete/{id}', name: 'app_back_vehicule_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, ?Vehicule $vehicule, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$vehicule) {
            error_log('Delete error: Vehicle not found');
            return new JsonResponse(['status' => 'error', 'message' => 'Véhicule non trouvé.'], 404);
        }

        try {
            $id = $vehicule->getId_vehicule();
            $entityManager->remove($vehicule);
            $entityManager->flush();
            error_log('Vehicle deleted, ID: ' . $id);
            return new JsonResponse(['status' => 'success', 'message' => 'Véhicule supprimé avec succès.'], 200);
        } catch (\Exception $e) {
            error_log('Delete error: ' . $e->getMessage());
            return new JsonResponse(['status' => 'error', 'message' => 'Erreur serveur.'], 500);
        }
    }
}