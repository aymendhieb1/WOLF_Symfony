<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class BackReponseController extends AbstractController
{
    #[Route('/back/reponse', name: 'app_back_reponse')]
    public function index(): Response
    {
        return $this->render('back_reponse/TableReponse.html.twig', [
            'controller_name' => 'BackReponseController',
        ]);
    }
}
