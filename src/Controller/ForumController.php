<?php

namespace App\Controller;

use App\Entity\Forum;
use App\Entity\User;
use App\Form\ForumType;
use App\Repository\ForumRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/forum')]
class ForumController extends AbstractController
{
    #[Route('/', name: 'app_forum_index', methods: ['GET'])]
    public function index(ForumRepository $forumRepository): Response
    {
        // Equivalent to rechercher() and afficher()
        return $this->render('forum/index.html.twig', [
            'forums' => $forumRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_forum_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Equivalent to ajouter()
        $forum = new Forum();
        $form = $this->createForm(ForumType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($forum);
            $entityManager->flush();

            $this->addFlash('success', '✅ Forum added successfully!');
            return $this->redirectToRoute('app_forum_index');
        }

        return $this->render('forum/new.html.twig', [
            'forum' => $forum,
            'form' => $form,
        ]);
    }

    #[Route('/{forum_id}', name: 'app_forum_show', methods: ['GET'])]
    public function show(Forum $forum): Response
    {
        return $this->render('forum/show.html.twig', [
            'forum' => $forum,
        ]);
    }

    #[Route('/{forum_id}/edit', name: 'app_forum_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Forum $forum, 
        EntityManagerInterface $entityManager
    ): Response {
        // Equivalent to modifier()
        $form = $this->createForm(ForumType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', '✅ Forum updated successfully!');
            return $this->redirectToRoute('app_forum_index');
        }

        return $this->render('forum/edit.html.twig', [
            'forum' => $forum,
            'form' => $form,
        ]);
    }

    #[Route('/{forum_id}', name: 'app_forum_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Forum $forum, 
        EntityManagerInterface $entityManager
    ): Response {
        // Equivalent to supprimer()
        if ($this->isCsrfTokenValid('delete'.$forum->getForumId(), $request->request->get('_token'))) {
            $entityManager->remove($forum);
            $entityManager->flush();
            $this->addFlash('success', '✅ Forum deleted successfully!');
        }

        return $this->redirectToRoute('app_forum_index');
    }

    #[Route('/user/by-email/{email}', name: 'app_forum_user_by_email', methods: ['GET'])]
    public function getUserByEmail(string $email, UserRepository $userRepository): Response
    {
        // Equivalent to getUserByEmail()
        $user = $userRepository->findOneBy(['mail' => $email]);
        
        return $this->json([
            'user_id' => $user ? $user->getIdUser() : null
        ]);
    }

    #[Route('/user/{id}/email', name: 'app_forum_user_email', methods: ['GET'])]
    public function getEmailByUserId(User $user): Response
    {
        // Equivalent to getEmailById()
        return $this->json([
            'email' => $user->getMail()
        ]);
    }

    #[Route('/user/{id}/fullname', name: 'app_forum_user_fullname', methods: ['GET'])]
    public function getUserFullNameById(User $user): Response
    {
        // Equivalent to getUserFullNameById()
        return $this->json([
            'fullname' => $user->getPrenom().' '.$user->getNom()
        ]);
    }
}