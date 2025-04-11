<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class BackReclamationController extends AbstractController
{
    #[Route('/back/reclamation', name: 'app_back_reclamation')]
    public function index(): Response
    {
        return $this->render('back_reclamation/TableReclamation.html.twig', [
            'controller_name' => 'BackReclamationController',
        ]);
    }
}
