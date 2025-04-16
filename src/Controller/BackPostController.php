<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/back/post')]
final class BackPostController extends AbstractController
{
    private const UPLOAD_DIR = '/public/uploads/';

    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/', name: 'app_back_post', methods: ['GET'])]
    public function index(): Response
    {
        $createForm = $this->createForm(PostType::class, new Post(), [
            'action' => $this->generateUrl('app_back_post_new')
        ]);

        $posts = $this->postRepository->findAll();
        $editForms = [];
        
        foreach ($posts as $post) {
            $editForms[$post->getPostId()] = $this->createForm(PostType::class, $post, [
                'action' => $this->generateUrl('app_back_post_edit', ['post_id' => $post->getPostId()])
            ])->createView();
        }

        return $this->render('back_post/TablePost.html.twig', [
            'posts' => $posts,
            'create_form' => $createForm->createView(),
            'edit_forms' => $editForms
        ]);
    }

    #[Route('/new', name: 'app_back_post_new', methods: ['POST'])]
    public function new(Request $request): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Ensure type is set
            if (!$post->getType()) {
                $this->addFlash('error', 'Type is required');
                return $this->redirectToRoute('app_back_post');
            }

            $this->handleFileUpload($form, $post);
            
            $post->setDateCreation(new \DateTime());
            $post->setDateModification(new \DateTime());
            
            $this->entityManager->persist($post);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Post created successfully!');
            return $this->redirectToRoute('app_back_post');
        }

        $this->addFlash('error', 'There were errors in your submission');
        return $this->redirectToRoute('app_back_post');
    }

    #[Route('/edit/{post_id}/form', name: 'app_back_post_edit_form', methods: ['POST'])]
    public function editForm(int $post_id): Response
    {
        $post = $this->postRepository->find($post_id);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        $form = $this->createForm(PostType::class, $post, [
            'action' => $this->generateUrl('app_back_post_edit', ['post_id' => $post_id])
        ]);

        return $this->render('post/_Post.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/edit/{post_id}', name: 'app_back_post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $post_id): Response
    {
        $post = $this->postRepository->find($post_id);
        if (!$post) {
            $this->addFlash('error', 'Post not found');
            return $this->redirectToRoute('app_back_post');
        }

        $form = $this->createForm(PostType::class, $post, [
            'action' => $this->generateUrl('app_back_post_edit', ['post_id' => $post_id])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Ensure type is set
                if (!$post->getType()) {
                    $this->addFlash('error', 'Type is required');
                    return $this->redirectToRoute('app_back_post');
                }

                $this->handleFileUpload($form, $post);
                $post->setDateModification(new \DateTime());
                $this->entityManager->flush();
                $this->addFlash('success', 'Post updated successfully!');
                return $this->redirectToRoute('app_back_post');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while updating the post');
            }
        }

        // Prepare all edit forms like in the index action
        $posts = $this->postRepository->findAll();
        $editForms = [];
        foreach ($posts as $p) {
            $editForms[$p->getPostId()] = $this->createForm(PostType::class, $p, [
                'action' => $this->generateUrl('app_back_post_edit', ['post_id' => $p->getPostId()])
            ])->createView();
        }

        return $this->render('back_post/TablePost.html.twig', [
            'posts' => $posts,
            'create_form' => $this->createForm(PostType::class, new Post(), [
                'action' => $this->generateUrl('app_back_post_new')
            ])->createView(),
            'edit_forms' => $editForms
        ]);
    }

    #[Route('/{post_id}', name: 'app_back_post_delete', methods: ['DELETE', 'POST'])]
    public function delete(Request $request, int $post_id): Response
    {
        $post = $this->postRepository->find($post_id);
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        if ($this->isCsrfTokenValid('delete'.$post_id, $request->request->get('_token'))) {
            // Delete associated file if exists
            if ($post->getCheminFichier()) {
                $filePath = $this->getParameter('kernel.project_dir').self::UPLOAD_DIR.$post->getCheminFichier();
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            $this->entityManager->remove($post);
            $this->entityManager->flush();
            $this->addFlash('success', 'Post deleted successfully!');
        }

        return $this->redirectToRoute('app_back_post');
    }

    #[Route('/type/{type}', name: 'app_back_post_by_type', methods: ['GET'])]
    public function findByType(string $type): Response
    {
        return $this->render('back_post/TablePost.html.twig', [
            'posts' => $this->postRepository->findBy(['type' => $type]),
            'create_form' => $this->createForm(PostType::class, new Post())->createView(),
            'current_type' => $type
        ]);
    }

    private function handleFileUpload($form, Post $post, ?string $originalFile = null): void
    {
        /** @var UploadedFile|null $file */
        $file = $form->get('chemin_fichier')->getData();
        
        if ($file) {
            $projectDir = $this->getParameter('kernel.project_dir');
            $uploadPath = $projectDir.self::UPLOAD_DIR;
            
            // Generate unique filename
            $newFilename = uniqid().'.'.$file->guessExtension();
            
            // Move the file
            $file->move($uploadPath, $newFilename);
            
            // Delete old file if exists
            if ($originalFile && file_exists($uploadPath.$originalFile)) {
                unlink($uploadPath.$originalFile);
            }
            
            $post->setCheminFichier($newFilename);
        } elseif ($originalFile) {
            $post->setCheminFichier($originalFile);
        }
    }
}