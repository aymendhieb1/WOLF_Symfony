<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SeconnecterController extends AbstractController
{
    #[Route('/seconnecter', name: 'app_seconnecter')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Récupérer la dernière erreur de connexion (s'il y en a une)
        $error = $authenticationUtils->getLastAuthenticationError();

        // Récupérer le dernier identifiant saisi par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('seconnecter/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
