<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User; // Import de l'entité User


class BackOfficeController extends AbstractController
{
    #[Route('/back/user', name: 'app_back_office')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Récupération des utilisateurs avec role = 2
        $userRepository = $entityManager->getRepository(User::class);
        $users = $userRepository->findBy(['role' => 2]);

        // Passage des données à la vue
        return $this->render('back_office/TableUser.html.twig', [
            'controller_name' => 'BackOfficeController',
            'users' => $users,
        ]);
    }
    #[Route('/back/user/{id}/update-status', name: 'user_update_status')]
    public function updateStatus(EntityManagerInterface $entityManager, int $id): Response
    {
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable');
        }

        if ($user->getStatus() === 0) {
            $user->setStatus(1);
        }

        $entityManager->flush();



        return $this->redirectToRoute('app_back_office');
    }
    #[Route('/back/user/{id}/update-status-debloquer', name: 'user_update_debloquer')]
    public function updateStatusdebloquer(EntityManagerInterface $entityManager, int $id): Response
    {
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable');
        }

        if ($user->getStatus() === 1) {
            $user->setStatus(0);
        }

        $entityManager->flush();


        return $this->redirectToRoute('app_back_office');
    }
    #[Route('/back/user/{id}/delete', name: 'user_delete')]
    public function delete(EntityManagerInterface $entityManager, int $id): Response
    {
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable');
        }

        $entityManager->remove($user);
        $entityManager->flush();


        return $this->redirectToRoute('app_back_office');
    }


}
