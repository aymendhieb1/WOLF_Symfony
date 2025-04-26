<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditUserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class EditerProfilerController extends AbstractController
{
    #[Route('/profiler/edit', name: 'app_editer_profiler')]
    public function editProfile(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour modifier votre profil.');
            return $this->redirectToRoute('app_seconnecter');
        }

        $form = $this->createForm(EditUserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $oldPassword = $form->get('mdp')->getData();
            $newPassword = $form->get('newPassword')->getData();


            if (!empty($oldPassword) && !empty($newPassword)) {
                if (!$passwordHasher->isPasswordValid($user, $oldPassword)) {
                    $this->addFlash('error', "Ancien mot de passe invalide.");
                    return $this->redirectToRoute('app_editer_profiler');
                }


                $hashedNewPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setMdp($hashedNewPassword);
            }

            $photoFile = $form->get('photo_profil')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('profile_pictures_directory'),
                        $newFilename
                    );
                    $user->setPhotoProfil($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo.');
                }
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('app_user_profiler');
        }

        return $this->render('editer_profiler/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}


