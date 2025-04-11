<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode peut rester vide, elle sera interceptée par le firewall.');
    }
    protected function requireUser()
    {
        if (!$this->getUser()) {
            $this->addFlash('warning', 'Veuillez vous connecter avant de continuer.');
            return $this->redirectToRoute('app_seconnecter');
        }
        // Si l'utilisateur est connecté, la méthode continue et retourne null
        return null;
    }


}
