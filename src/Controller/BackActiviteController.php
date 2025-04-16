<?php

namespace App\Controller;

use App\Repository\ActiviteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class BackActiviteController extends AbstractController
{
    #[Route('/back/activite', name: 'app_back_activite')]
    public function index(ActiviteRepository $activiteRepository): Response
    {
        return $this->render('back_activite/TableActivite.html.twig', [
            'activites' => $activiteRepository->findAll(),
        ]);
    }
}
