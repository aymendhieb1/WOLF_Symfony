<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class BackPostController extends AbstractController
{
    #[Route('/back/post', name: 'app_back_post')]
    public function index(): Response
    {
        return $this->render('back_post/TablePost.html.twig', [
            'controller_name' => 'BackPostController',
        ]);
    }
}
