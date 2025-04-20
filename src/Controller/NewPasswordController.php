<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class NewPasswordController extends AbstractController
{
    #[Route('/reinitialiser-par-code', name: 'app_reset_code_verification')]

    public function resetPassword(
        Request $request,
        UserRepository $userRepo,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        $session = $request->getSession();

        $email = $session->get('reset_email');
        $codeSession = $session->get('reset_code');

        if (!$email || !$codeSession) {
            $this->addFlash('danger', 'Accès non autorisé ou session expirée.');
            return $this->redirectToRoute('app_reset_password');
        }

        $user = $userRepo->findOneBy(['mail' => $email]);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setMdp($hashedPassword);

            $em->flush();

            $session->remove('reset_code');
            $session->remove('reset_email');

            $this->addFlash('success', 'Mot de passe réinitialisé avec succès.');
            return $this->redirectToRoute('app_seconnecter');
        }

        return $this->render('new_password/index.html.twig');
    }

}
