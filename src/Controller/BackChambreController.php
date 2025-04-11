<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class BackChambreController extends AbstractController
{
    #[Route('/back/chambre', name: 'app_back_chambre')]
    public function index(): Response
    {
        return $this->render('back_chambre/TableChambre.html.twig', [
            'controller_name' => 'BackChambreController',
        ]);
    }
}
