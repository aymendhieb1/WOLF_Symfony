<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class SinscrireController extends AbstractController
{
    #[Route('/signin', name: 'app_sinscrire')]
    public function sinscrire(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        // 1. Créer une instance de User
        $user = new User();

        // 2. Créer le formulaire à partir de votre UserType
        $form = $this->createForm(UserType::class, $user);

        // 3. Traiter la requête
        $form->handleRequest($request);

        // 4. Vérifier si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {

            // 4.a. Gestion de l'upload de la photo de profil
            // Récupérer le fichier du champ photoProfilFile (champ FileType non mappé)
            $photoFile = $form->get('photo_profil')->getData();
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
                } catch (FileException $e) {
                    // Gérer l'exception en ajoutant un message flash, par exemple
                    $this->addFlash('error', 'Impossible de télécharger votre photo. Veuillez réessayer.');
                }
                // Mettre à jour la propriété photo_profil de l'entité
                $user->setPhotoProfil($newFilename);
            }

            // Récupérer le mot de passe en clair soumis dans le formulaire
            $plainPassword = $form->get('mdp')->getData();
            // 5. Hacher le mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setMdp($hashedPassword);

            // 6. Gérer le rôle et le statut par défaut (si nécessaire)
            $user->setRole(2);
            $user->setStatus(0);

            // 7. Persister et sauvegarder l'utilisateur
            $entityManager->persist($user);
            $entityManager->flush();

            // 8. Message flash ou redirection
            $this->addFlash('success', 'Compte créé avec succès !');

            return $this->redirectToRoute('app_seconnecter');
        }

        // 9. Rendre la vue avec le formulaire
        return $this->render('sinscrire/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
