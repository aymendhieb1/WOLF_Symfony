<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class BackVolController extends AbstractController
{
    #[Route('/back/vol', name: 'app_back_vol')]
    public function index(): Response
    {
        return $this->render('back_vol/TableVol.html.twig', [
            'controller_name' => 'BackVolController',
        ]);
    }
}
