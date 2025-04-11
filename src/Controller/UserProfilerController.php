<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class UserProfilerController extends AbstractController
{
    #[Route('/user/profiler', name: 'app_user_profiler')]
    public function index(): Response
    {
        return $this->render('user_profiler/index.html.twig', [
            'controller_name' => 'UserProfilerController',
        ]);
    }

}
