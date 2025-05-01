<?php

namespace App\Controller;

use App\Entity\Session;
use App\Form\SessionType;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/session')]
class SessionController extends AbstractController
{
    #[Route('/', name: 'app_session_index', methods: ['GET'])]
    public function index(SessionRepository $sessionRepository): Response
    {
        return $this->render('session/index.html.twig', [
            'sessions' => $sessionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_session_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $session = new Session();
        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($session);
            $entityManager->flush();

            return $this->redirectToRoute('app_back_session');
        }

        return $this->render('session/new.html.twig', [
            'session' => $session,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_session_show', methods: ['GET'])]
    public function show(int $id, SessionRepository $sessionRepository): Response
    {
        $session = $sessionRepository->find($id);
        
        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }

        return $this->render('session/show.html.twig', [
            'session' => $session,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_session_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, SessionRepository $sessionRepository, EntityManagerInterface $entityManager): Response
    {
        $session = $sessionRepository->find($id);
        
        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }

        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_back_session');
        }

        return $this->render('session/edit.html.twig', [
            'session' => $session,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_session_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, SessionRepository $sessionRepository, EntityManagerInterface $entityManager): Response
    {
        $session = $sessionRepository->find($id);
        
        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }

        if ($this->isCsrfTokenValid('delete'.$session->getId(), $request->request->get('_token'))) {
            $entityManager->remove($session);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_back_session');
    }

    #[Route('/api/sessions/activity/{id}', name: 'app_api_sessions_by_activity', methods: ['GET'])]
    public function getSessionsByActivity(int $id, SessionRepository $sessionRepository): JsonResponse
    {
        $sessions = $sessionRepository->findBy(['activite' => $id]);
        
        $data = [];
        foreach ($sessions as $session) {
            $data[] = [
                'id' => $session->getId(),
                'date' => $session->getDate()->format('Y-m-d'),
                'heure' => $session->getHeure()->format('H:i'),
                'nbPlace' => $session->getNbPlace()
            ];
        }
        
        return new JsonResponse($data);
    }

    #[Route('/api/all-sessions', name: 'app_session_api_all', methods: ['GET'])]
    public function getAllSessions(SessionRepository $sessionRepository): JsonResponse
    {
        $sessions = $sessionRepository->findAll();
        
        $data = [];
        foreach ($sessions as $session) {
            $data[] = [
                'id' => $session->getId(),
                'date' => $session->getDate()->format('Y-m-d'),
                'heure' => $session->getHeure()->format('H:i:s'),
                'capacite' => $session->getCapacite(),
                'nbPlace' => $session->getNbPlace(),
                'activite' => [
                    'id' => $session->getActivite()->getId(),
                    'nom' => $session->getActivite()->getNom(),
                    'type' => $session->getActivite()->getType(),
                ]
            ];
        }
        
        return new JsonResponse($data);
    }
} 