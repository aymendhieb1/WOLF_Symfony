<?php

namespace App\Controller;
use App\Entity\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class UserProfilerController extends AbstractController
{
    #[Route('/user/profiler', name: 'app_user_profiler')]
    public function index(): Response
    {  /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour voir votre profil.');
            return $this->redirectToRoute('app_seconnecter');
        }
        return $this->render('user_profiler/index.html.twig', [
            'controller_name' => 'UserProfilerController',
        ]);
    }

}
