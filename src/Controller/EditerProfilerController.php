<?php

namespace App\Controller;

use App\Entity\User;
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
        // Récupérer l'utilisateur connecté
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour modifier votre profil.');
            return $this->redirectToRoute('app_seconnecter');
        }

        // Vérifier si le formulaire a été soumis (POST)
        if ($request->isMethod('POST')) {
            // Mise à jour des informations textuelles
            $prenom = $request->request->get('prenom');
            $nom = $request->request->get('nom');
            $numTel = $request->request->get('numTel');
            $mail = $request->request->get('mail');

            $user->setPrenom($prenom);
            $user->setNom($nom);
            $user->setNumTel($numTel);
            $user->setMail($mail);

            // Gestion de la mise à jour du mot de passe
            $oldPassword = $request->request->get('oldPassword');
            $newPassword = $request->request->get('newPassword');
            if (!empty($oldPassword) && !empty($newPassword)) {
                // Vérifier que l'ancien mot de passe est correct
                if ($passwordHasher->isPasswordValid($user, $oldPassword)) {
                    $hashedNewPassword = $passwordHasher->hashPassword($user, $newPassword);
                    $user->setMdp($hashedNewPassword);
                } else {
                    $this->addFlash('error', 'Ancien mot de passe invalide.');
                    return $this->redirectToRoute('app_editer_profiler');
                }
            }

            // Gestion de l'upload de la photo de profil
            $photoFile = $request->files->get('photoProfil');
            if ($photoFile) {
                // Générer un nom de fichier sécurisé
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                // Déplacer le fichier dans le répertoire défini dans vos paramètres
                try {
                    $photoFile->move(
                        $this->getParameter('profile_pictures_directory'),
                        $newFilename
                    );
                    // Mettre à jour l'entité avec le nouveau nom de fichier
                    $user->setPhotoProfil($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo.');
                }
            }

            // Sauvegarder en base de données
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('app_user_profiler');
        }

        // Afficher le formulaire d'édition de profil (la vue doit correspondre à votre template déjà développé)
        return $this->render('editer_profiler/index.html.twig', [
            // Vous pouvez passer des variables supplémentaires si nécessaire
        ]);
    }
}
