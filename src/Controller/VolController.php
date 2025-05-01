<?php

namespace App\Controller;

use App\Entity\Vol;
use App\Repository\VolRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/vol')]
class VolController extends AbstractController
{
    #[Route('/', name: 'app_vol_index', methods: ['GET'])]
    public function index(VolRepository $volRepository): Response
    {
        $vols = $volRepository->findAll();

        return $this->render('vol/index.html.twig', [
            'vols' => $vols
        ]);
    }

    #[Route('/{id}', name: 'app_vol_show', methods: ['GET'])]
    public function show(Vol $vol): Response
    {
        return $this->render('vol/show_with_weather.html.twig', [
            'vol' => $vol
        ]);
    }
} 