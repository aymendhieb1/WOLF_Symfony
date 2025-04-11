<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class BackContratController extends AbstractController
{
    #[Route('/back/contrat', name: 'app_back_contrat')]
    public function index(): Response
    {
        return $this->render('back_contrat/TableContrat.html.twig', [
            'controller_name' => 'BackContratController',
        ]);
    }
}
