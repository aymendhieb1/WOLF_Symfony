<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class BackForumController extends AbstractController
{
    #[Route('/back/forum', name: 'app_back_forum')]
    public function index(): Response
    {
        return $this->render('back_forum/TableForum.html.twig', [
            'controller_name' => 'BackForumController',
        ]);
    }
}
