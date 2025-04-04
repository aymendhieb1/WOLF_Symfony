<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BackOfficeController extends AbstractController
{
    #[Route('/back/user', name: 'app_back_office')]
    public function index(): Response
    {
        
        return $this->render('back_office/TableUser.html.twig', [
            'controller_name' => 'BackOfficeController',
        ]);
    }
}
