<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class BackVehiculeController extends AbstractController
{
    #[Route('/back/vehicule', name: 'app_back_vehicule')]
    public function index(): Response
    {
        return $this->render('back_vehicule/TableVehicule.html.twig', [
            'controller_name' => 'BackVehiculeController',
        ]);
    }
}
