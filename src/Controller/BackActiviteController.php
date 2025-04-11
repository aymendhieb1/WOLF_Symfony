<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class BackActiviteController extends AbstractController
{
    #[Route('/back/activite', name: 'app_back_activite')]
    public function index(): Response
    {
        return $this->render('back_activite/TableActivite.html.twig', [
            'controller_name' => 'BackActiviteController',
        ]);
    }
}
