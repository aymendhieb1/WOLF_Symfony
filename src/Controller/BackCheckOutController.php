<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class BackCheckOutController extends AbstractController
{
    #[Route('/back/check/out', name: 'app_back_checkout')]
    public function index(): Response
    {
        return $this->render('back_check_out/TableCheckOut.html.twig', [
            'controller_name' => 'BackCheckOutController',
        ]);
    }
}
