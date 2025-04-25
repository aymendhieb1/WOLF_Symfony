<?php

namespace App\Controller;

use App\Entity\Chambre;
use App\Form\ChambreType;
use App\Repository\ChambreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/back/chambre')]
class ChambreController extends AbstractController
{
    #[Route('/', name: 'app_back_chambre', methods: ['GET'])]
    public function index(ChambreRepository $chambreRepository): Response
    {
        $chambres = $chambreRepository->findAll();
        return $this->render('back_chambre/TableChambre.html.twig', [
            'chambres' => $chambres,
        ]);
    }

    #[Route('/new', name: 'app_back_chambre_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $chambre = new Chambre();
        $form = $this->createForm(ChambreType::class, $chambre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($chambre);
            $entityManager->flush();

            return $this->redirectToRoute('app_back_chambre', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back_chambre/new.html.twig', [
            'chambre' => $chambre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/show', name: 'app_back_chambre_show', methods: ['GET'])]
    public function show(int $id, ChambreRepository $chambreRepository): Response
    {
        $chambre = $chambreRepository->find($id);
        
        if (!$chambre) {
            throw $this->createNotFoundException('Chambre not found');
        }

        return $this->render('back_chambre/show.html.twig', [
            'chambre' => $chambre,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_back_chambre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, EntityManagerInterface $entityManager, ChambreRepository $chambreRepository): Response
    {
        $chambre = $chambreRepository->find($id);
        
        if (!$chambre) {
            throw $this->createNotFoundException('Chambre not found');
        }

        $form = $this->createForm(ChambreType::class, $chambre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_back_chambre', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back_chambre/edit.html.twig', [
            'chambre' => $chambre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_back_chambre_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, int $id, EntityManagerInterface $entityManager, ChambreRepository $chambreRepository): Response
    {
        $chambre = $chambreRepository->find($id);
        
        if (!$chambre) {
            throw $this->createNotFoundException('Chambre not found');
        }

        if ($this->isCsrfTokenValid('delete'.$chambre->getId(), $request->request->get('_token'))) {
            $entityManager->remove($chambre);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_back_chambre', [], Response::HTTP_SEE_OTHER);
    }
} 