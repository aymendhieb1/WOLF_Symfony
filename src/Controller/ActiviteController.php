<?php

namespace App\Controller;

use App\Entity\Activite;
use App\Form\ActiviteType;
use App\Repository\ActiviteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/activite')]
class ActiviteController extends AbstractController
{
    #[Route('/', name: 'app_activite_index', methods: ['GET'])]
    public function index(ActiviteRepository $activiteRepository): Response
    {
        $activities = $activiteRepository->findAll();
        
        // Calculate activity type statistics
        $typeStats = [];
        $total = count($activities);
        
        foreach ($activities as $activity) {
            $type = $activity->getType();
            if (!isset($typeStats[$type])) {
                $typeStats[$type] = 0;
            }
            $typeStats[$type]++;
        }
        
        $activityStats = [
            'total' => $total,
            'labels' => array_keys($typeStats),
            'data' => array_values($typeStats)
        ];

        return $this->render('back_activite/TableActivite.html.twig', [
            'activites' => $activities,
            'activityStats' => $activityStats
        ]);
    }

    #[Route('/new', name: 'app_activite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $activite = new Activite();
        $form = $this->createForm(ActiviteType::class, $activite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($activite);
            $entityManager->flush();

            return $this->redirectToRoute('app_back_activite', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activite/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_activite_show', methods: ['GET'])]
    public function show(int $id, ActiviteRepository $activiteRepository): Response
    {
        $activite = $activiteRepository->find($id);
        if (!$activite) {
            throw $this->createNotFoundException('Activité non trouvée');
        }

        return $this->render('activite/show.html.twig', [
            'activite' => $activite,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_activite_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, ActiviteRepository $activiteRepository, EntityManagerInterface $entityManager): Response
    {
        $activite = $activiteRepository->find($id);
        if (!$activite) {
            throw $this->createNotFoundException('Activité non trouvée');
        }

        $form = $this->createForm(ActiviteType::class, $activite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_back_activite', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activite/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_activite_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, ActiviteRepository $activiteRepository, EntityManagerInterface $entityManager): Response
    {
        $activite = $activiteRepository->find($id);
        if (!$activite) {
            throw $this->createNotFoundException('Activité non trouvée');
        }

        if ($this->isCsrfTokenValid('delete'.$activite->getId(), $request->request->get('_token'))) {
            $entityManager->remove($activite);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_back_activite', [], Response::HTTP_SEE_OTHER);
    }
} 