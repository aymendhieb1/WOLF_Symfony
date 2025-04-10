<?php

namespace App\Controller;

use App\Entity\Forum;
use App\Form\ForumType;
use App\Repository\ForumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/back/forum')]
final class BackForumController extends AbstractController
{
    public function __construct(
        private readonly ForumRepository $forumRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/', name: 'app_back_forum', methods: ['GET'])]
    public function index(): Response
    {
        $createForm = $this->createForm(ForumType::class, new Forum(), [
            'action' => $this->generateUrl('app_back_forum_new')
        ]);

        $forums = $this->forumRepository->findAll();
        $editForms = [];
        
        foreach ($forums as $forum) {
            $editForms[$forum->getForumId()] = $this->createForm(ForumType::class, $forum, [
                'action' => $this->generateUrl('app_back_forum_edit', ['forum_id' => $forum->getForumId()])
            ])->createView();
        }

        return $this->render('back_forum/TableForum.html.twig', [
            'forums' => $forums,
            'create_form' => $createForm->createView(),
            'edit_forms' => $editForms
        ]);
    }

    #[Route('/new', name: 'app_back_forum_new', methods: ['POST'])]
    public function new(Request $request): Response
    {
        $forum = new Forum();
        $form = $this->createForm(ForumType::class, $forum);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $this->entityManager->persist($forum);
                    $this->entityManager->flush();
                    $this->addFlash('success', 'Forum created successfully!');
                    return $this->redirectToRoute('app_back_forum');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'An error occurred while creating the forum: ' . $e->getMessage());
                }
            } else {
                // Get form errors
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->addFlash('error', 'There were errors in your submission: ' . implode(', ', $errors));
            }
        }

        return $this->redirectToRoute('app_back_forum');
    }

    #[Route('/edit/{forum_id}', name: 'app_back_forum_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $forum_id): Response
    {
        $forum = $this->forumRepository->find($forum_id);
        if (!$forum) {
            $this->addFlash('error', 'Forum not found');
            return $this->redirectToRoute('app_back_forum');
        }
    
        $form = $this->createForm(ForumType::class, $forum, [
            'action' => $this->generateUrl('app_back_forum_edit', ['forum_id' => $forum_id])
        ]);
        $form->handleRequest($request);
    
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $this->entityManager->flush();
                    $this->addFlash('success', 'Forum updated successfully!');
                    return $this->redirectToRoute('app_back_forum');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'An error occurred while updating the forum');
                }
            } else {
                $this->addFlash('error', 'There were errors in your submission. Please check the form.');
            }
        }
    
        // Prepare all edit forms like in the index action
        $forums = $this->forumRepository->findAll();
        $editForms = [];
        foreach ($forums as $f) {
            $editForms[$f->getForumId()] = $this->createForm(ForumType::class, $f, [
                'action' => $this->generateUrl('app_back_forum_edit', ['forum_id' => $f->getForumId()])
            ])->createView();
        }
    
        return $this->render('back_forum/TableForum.html.twig', [
            'forums' => $forums,
            'create_form' => $this->createForm(ForumType::class, new Forum(), [
                'action' => $this->generateUrl('app_back_forum_new')
            ])->createView(),
            'edit_forms' => $editForms,
            'edit_forum_id' => $forum_id
        ]);
    }
    #[Route('/{forum_id}', name: 'app_back_forum_delete', methods: ['DELETE', 'POST'])]
    public function delete(Request $request, int $forum_id): Response
    {
        $forum = $this->forumRepository->find($forum_id);
        if (!$forum) {
            throw $this->createNotFoundException('Forum not found');
        }

        if ($this->isCsrfTokenValid('delete'.$forum_id, $request->request->get('_token'))) {
            $this->entityManager->remove($forum);
            $this->entityManager->flush();
            $this->addFlash('success', 'Forum deleted successfully!');
        }

        return $this->redirectToRoute('app_back_forum');
    }
}