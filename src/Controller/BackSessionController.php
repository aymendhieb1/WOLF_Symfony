<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class BackSessionController extends AbstractController
{
    #[Route('/back/session', name: 'app_back_session')]
    public function index(): Response
    {
        return $this->render('back_session/TableSession.html.twig', [
            'controller_name' => 'BackSessionController',
        ]);
    }
}
