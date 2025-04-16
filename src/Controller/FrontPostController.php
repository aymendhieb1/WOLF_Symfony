<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Forum;
use App\Entity\Comment;
use App\Form\PostType;
use App\Form\CommentType;
use App\Repository\PostRepository;
use App\Repository\ForumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/front/post')]
class FrontPostController extends AbstractController
{
    private const UPLOAD_DIR = '/public/uploads/';
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    public function __construct(
        private PostRepository $postRepository,
        private ForumRepository $forumRepository,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SluggerInterface $slugger
    ) {}

    #[Route('/', name: 'app_front_post_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->security->getUser();
        $userId = $user?->getId();
    
        $forums = $this->forumRepository->findAccessibleForums($userId);
        $forumId = $request->query->get('forum');
        $search = $request->query->get('search');
        
        $currentForum = $forumId ? $this->forumRepository->find($forumId) : null;
        
        $posts = $this->postRepository->findByForumAndSearch(
            $forumId,
            $search,
            $userId
        );

        $createForm = $this->createForm(PostType::class, new Post(), [
            'action' => $this->generateUrl('app_front_post_new')
        ]);

        return $this->render('post/FrontPost.html.twig', [
            'posts' => $posts,
            'forums' => $forums,
            'currentForum' => $currentForum,
            'searchTerm' => $search,
            'currentTab' => $request->query->get('tab', 'posts'),
            'create_form' => $createForm->createView(),
            'current_user' => $user,
        ]);
    }

    #[Route('/new', name: 'app_front_post_new', methods: ['POST'])]
    public function new(Request $request): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleFileUpload($form, $post);
            
            $post->setDateCreation(new \DateTime());
            $post->setDateModification(new \DateTime());
            
            $this->entityManager->persist($post);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Post created successfully!');
            return $this->redirectToRoute('app_front_post_index');
        }

        $this->addFlash('error', 'There were errors in your submission');
        return $this->redirectToRoute('app_front_post_index');
    }

    #[Route('/{id}', name: 'app_front_post_show', methods: ['GET', 'POST'])]
    public function show(Post $post, Request $request): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $post->getForum());

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setPost($post)
                ->setUser($this->security->getUser())
                ->setDateCreation(new \DateTime());
            
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $this->addFlash('success', 'Comment added successfully!');
            return $this->redirectToRoute('app_front_post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'commentForm' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_front_post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $post);

        $originalFile = $post->getCheminFichier();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->handleFileUpload($form, $post, $originalFile);
                
                $post->setDateModification(new \DateTime());
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Post updated successfully!');
                return $this->redirectToRoute('app_front_post_show', ['id' => $post->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating post: '.$e->getMessage());
            }
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_front_post_delete', methods: ['DELETE', 'POST'])]
    public function delete(Request $request, Post $post): Response
    {
        $this->denyAccessUnlessGranted('DELETE', $post);

        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            try {
                $this->deletePostFile($post);
                
                $this->entityManager->remove($post);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Post deleted successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting post: '.$e->getMessage());
            }
        }

        return $this->redirectToRoute('app_front_post_index');
    }

    #[Route('/{id}/vote/{type}', name: 'app_front_post_vote', methods: ['POST'])]
    public function vote(Post $post, string $type): Response
    {
        $this->denyAccessUnlessGranted('VOTE', $post);
        $user = $this->security->getUser();
        $userEmail = $user->getMail();

        $upVoteList = $post->getUpVoteList() ? explode(',', $post->getUpVoteList()) : [];
        $downVoteList = $post->getDownVoteList() ? explode(',', $post->getDownVoteList()) : [];
        
        if ($type === 'up') {
            if (!in_array($userEmail, $upVoteList)) {
                // Remove from downvote list if exists
                if (($key = array_search($userEmail, $downVoteList)) !== false) {
                    unset($downVoteList[$key]);
                    $post->setDownVoteList(implode(',', $downVoteList));
                }
                
                $upVoteList[] = $userEmail;
                $post->setUpVoteList(implode(',', $upVoteList));
                $post->setVotes($post->getVotes() + 1);
            }
        } else {
            if (!in_array($userEmail, $downVoteList)) {
                // Remove from upvote list if exists
                if (($key = array_search($userEmail, $upVoteList)) !== false) {
                    unset($upVoteList[$key]);
                    $post->setUpVoteList(implode(',', $upVoteList));
                }
                
                $downVoteList[] = $userEmail;
                $post->setDownVoteList(implode(',', $downVoteList));
                $post->setVotes($post->getVotes() - 1);
            }
        }
        
        $this->entityManager->flush();
        
        return $this->json([
            'success' => true,
            'votes' => $post->getVotes(),
            'upVoteList' => $post->getUpVoteList(),
            'downVoteList' => $post->getDownVoteList()
        ]);
    }

    #[Route('/{id}/report', name: 'app_front_post_report', methods: ['POST'])]
    public function report(Post $post): Response
    {
        $this->denyAccessUnlessGranted('REPORT', $post);
        $user = $this->security->getUser();
        $userEmail = $user->getMail();

        $signalList = $post->getSignalList() ? explode(',', $post->getSignalList()) : [];
        
        if (!in_array($userEmail, $signalList)) {
            $signalList[] = $userEmail;
            $post->setSignalList(implode(',', $signalList));
            $post->setNbrSignal($post->getNbrSignal() + 1);
            
            $this->entityManager->flush();
        }
        
        return $this->json([
            'success' => true,
            'reports' => $post->getNbrSignal(),
            'signalList' => $post->getSignalList()
        ]);
    }

    private function handleFileUpload($form, Post $post, ?string $originalFile = null): void
    {
        /** @var UploadedFile|null $file */
        $file = $form->get('chemin_fichier')->getData();
        
        if ($file) {
            if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
                throw new \InvalidArgumentException('Invalid file type');
            }

            $projectDir = $this->getParameter('kernel.project_dir');
            $uploadPath = $projectDir.self::UPLOAD_DIR;
            
            // Generate unique filename
            $newFilename = uniqid().'.'.$file->guessExtension();
            
            // Move the file
            $file->move($uploadPath, $newFilename);
            
            // Delete old file if exists
            if ($originalFile) {
                $this->deleteFile($originalFile);
            }
            
            $post->setCheminFichier($newFilename);
        } elseif ($originalFile) {
            $post->setCheminFichier($originalFile);
        }
    }

    private function deletePostFile(Post $post): void
    {
        if ($post->getCheminFichier()) {
            $this->deleteFile($post->getCheminFichier());
        }
    }

    private function deleteFile(string $filename): void
    {
        $filePath = $this->getParameter('kernel.project_dir').self::UPLOAD_DIR.$filename;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}