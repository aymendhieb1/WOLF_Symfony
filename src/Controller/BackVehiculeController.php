<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Vehicule;
use Doctrine\ORM\EntityManagerInterface;

final class BackVehiculeController extends AbstractController
{
    #[Route('/back/vehicule', name: 'app_back_vehicule')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $VehiculeRepository = $entityManager->getRepository(Vehicule::class);
        $vehicule = $VehiculeRepository->findAll();

        return $this->render('back_vehicule/TableVehicule.html.twig', [
            'controller_name' => 'BackVehiculeController',
            'vehicules' =>$vehicule ,
        ]);
    }

    #[Route('/vehicule/new', name: 'app_vehicule_new')]
    public function new(Request $request): Response
    {
        $vehicule = new Vehicule();
        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($vehicule);
            $entityManager->flush();

            return $this->redirectToRoute('app_vehicule_index');
        }

        return $this->render('vehicule/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/vehicule/{id}', name: 'app_vehicule_show')]
    public function show(Vehicule $vehicule): Response
    {
        return $this->render('vehicule/show.html.twig', [
            'vehicule' => $vehicule,
        ]);
    }

    #[Route('/vehicule/{id}/edit', name: 'app_vehicule_edit')]
    public function edit(Request $request, Vehicule $vehicule): Response
    {
        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('app_vehicule_index');
        }

        return $this->render('vehicule/edit.html.twig', [
            'vehicule' => $vehicule,
            'form' => $form->createView(),
        ]);
    }
}

