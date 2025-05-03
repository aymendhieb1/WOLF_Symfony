<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Forum;
use App\Entity\Comment;
use App\Form\PostType;
use App\Form\CommentType;
use App\Form\PostEditType;
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
use App\Service\DisqusService;
use App\Service\BadWordService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Knp\Snappy\Pdf;
use App\Service\AutoBotCommentService;

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

    private $disqusService;
    private $badWordService;

    public function __construct(
        private PostRepository $postRepository,
        private ForumRepository $forumRepository,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SluggerInterface $slugger,
        DisqusService $disqusService,
        BadWordService $badWordService
    ) {
        $this->disqusService = $disqusService;
        $this->badWordService = $badWordService;
    }

    #[Route('/', name: 'app_front_post_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->security->getUser();
        $userId = $user?->getId();
<<<<<<< Updated upstream
    
        // Get accessible forums
        $forums = $this->forumRepository->findAccessibleForums($userId);
        
        // Get current forum ID from request
        $forumId = $request->query->get('forum');
        $searchTerm = $request->query->get('search');
        $sortBy = $request->query->get('sort', 'date');
        
        // Find current forum if ID is provided
        $currentForum = null;
        if ($forumId) {
            foreach ($forums as $forum) {
                if ($forum->getForumId() == $forumId) {
                    $currentForum = $forum;
                    break;
                }
            }
        }
        
        // Get posts for current forum
=======

        $forums = $this->forumRepository->findAccessibleForums($userId);

        $forumId = $request->query->get('forum');
        $searchTerm = $request->query->get('search');
        $sortBy = $request->query->get('sort', 'date');

        $currentForum = $forumId ? $this->forumRepository->find($forumId) : null;

>>>>>>> Stashed changes
        $posts = $this->postRepository->findByForumAndSearch(
            $forumId,
            $searchTerm,
            $userId,
            $sortBy
        );

        $createForm = $this->createForm(PostType::class, new Post(), [
            'action' => $this->generateUrl('app_front_post_new')
        ]);

<<<<<<< Updated upstream
        // Get Disqus configuration
=======
        // Create edit form for each post
        $editForms = [];
        foreach ($posts as $post) {
            $editForms[$post->getPostId()] = $this->createForm(PostEditType::class, $post, [
                'action' => $this->generateUrl('app_post_edit', ['id' => $post->getPostId()])
            ])->createView();
        }

>>>>>>> Stashed changes
        $disqusConfig = [
            'shortname' => 'triptogo-1',
            'url' => $request->getUri(),
            'identifier' => 'general',
            'title' => 'TripToGo Forum'
        ];

        if (!empty($posts)) {
            $disqusConfig = $this->disqusService->getConfig($posts[0]);
        }

        return $this->render('post/FrontPost.html.twig', [
            'posts' => $posts,
            'forums' => $forums,
            'currentForum' => $currentForum,
            'searchTerm' => $searchTerm,
            'sortBy' => $sortBy,
            'currentTab' => $request->query->get('tab', 'posts'),
            'create_form' => $createForm->createView(),
            'edit_forms' => $editForms,
            'current_user' => $user,
            'disqus_config' => $disqusConfig
        ]);
    }

    #[Route('/new', name: 'app_front_post_new', methods: ['POST'])]
    public function new(Request $request): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
<<<<<<< Updated upstream
            // Validate content using BadWordService
=======
            // Get the current user
            $user = $this->security->getUser();
            if (!$user) {
                $this->addFlash('error', 'You must be logged in to create a post.');
                return $this->redirectToRoute('app_front_post_index');
            }

            // Set the user for the post
            $post->setIdUser($user);

>>>>>>> Stashed changes
            if ($this->badWordService->validatePost($post)) {
                $this->addFlash('error', 'Your post contains inappropriate content. Please review and try again.');
                return $this->redirectToRoute('app_front_post_index');
            }

            $this->handleFileUpload($form, $post);

            $post->setDateCreation(new \DateTime());
            $post->setDateModification(new \DateTime());

            $this->entityManager->persist($post);
            $this->entityManager->flush();

<<<<<<< Updated upstream
            // Create an auto-comment after post creation
=======
>>>>>>> Stashed changes
            try {
                $commentResult = $this->disqusService->createComment(
                    $this->disqusService->getDefaultThreadId(),
                    'Auto-generated comment for new post: ' . $this->disqusService->getPostTitle($post)
                );
                
                if ($commentResult['success']) {
                    $this->addFlash('success', 'Post created successfully with auto-comment!');
                } else {
                    $this->addFlash('warning', 'Post created but auto-comment failed: ' . $commentResult['error']);
                }
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Post created but auto-comment failed: ' . $e->getMessage());
            }

            return $this->redirectToRoute('app_front_post_index');
        }

        $this->addFlash('error', 'There were errors in your submission');
        return $this->redirectToRoute('app_front_post_index');
    }

    #[Route('/update-posts', name: 'app_front_post_update', methods: ['GET'])]
    public function updatePosts(Request $request): JsonResponse
    {
        try {
            $user = $this->security->getUser();
            $userId = $user?->getId();
<<<<<<< Updated upstream
            
            $forumId = $request->query->get('forum');
            $searchTerm = $request->query->get('search');
            $sortBy = $request->query->get('sort', 'date');
            
=======

            $forumId = $request->query->get('forum');
            $searchTerm = $request->query->get('search');
            $sortBy = $request->query->get('sort', 'date');

>>>>>>> Stashed changes
            $posts = $this->postRepository->findByForumAndSearch(
                $forumId,
                $searchTerm,
                $userId,
                $sortBy
            );

            $html = $this->renderView('post/_posts.html.twig', [
                'posts' => $posts
            ]);

            return new JsonResponse([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/post/{id}', name: 'app_front_post_show')]
    public function show(Post $post, DisqusService $disqusService, AutoBotCommentService $autoBotCommentService): Response
    {
<<<<<<< Updated upstream
        // Create a Disqus thread for this post if it doesn't exist
        $threadResult = $disqusService->createThread($post);
        
=======

        $threadResult = $disqusService->createThread($post);

>>>>>>> Stashed changes
        if (!$threadResult['success']) {
            $this->addFlash('error', 'Failed to create discussion thread: ' . $threadResult['error']);
        }

<<<<<<< Updated upstream
        // Auto-comment on the post using the bot
=======
>>>>>>> Stashed changes
        $autoBotCommentService->autoCommentOnPost($post);

        return $this->render('front_post/show.html.twig', [
            'post' => $post,
            'thread' => $threadResult['thread'] ?? null
        ]);
    }

    #[Route('/{id}/show', name: 'app_front_post_show', methods: ['GET'])]
    public function showPostDetails(Post $post): Response
    {
        return $this->render('post/show.html.twig', [
            'post' => $post
        ]);
    }

    #[Route('/post/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post): Response
    {
        
        if ($post->getIdUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You are not authorized to edit this post.');
        }

        $form = $this->createForm(PostEditType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
<<<<<<< Updated upstream
            // Validate content using BadWordService
            if ($this->badWordService->validatePost($post)) {
                $this->addFlash('error', 'Your post contains inappropriate content. Please review and try again.');
                return $this->render('post/edit.html.twig', [
                    'post' => $post,
                    'form' => $form->createView(),
                ]);
            }

=======
            // Handle file upload if a new file was provided
            $file = $form->get('chemin_fichier')->getData();
            if ($file) {
                $this->handleFileUpload($form, $post, $post->getCheminFichier());
            }

            // Update modification date
            $post->setDateModification(new \DateTime());

>>>>>>> Stashed changes
            try {
                $this->entityManager->flush();

                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => true,
                        'message' => 'Post updated successfully'
                    ]);
                }

                $this->addFlash('success', 'Post updated successfully');
                return $this->redirectToRoute('app_front_post_index');
            } catch (\Exception $e) {
                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Error updating post: ' . $e->getMessage()
                    ], 500);
                }

                $this->addFlash('error', 'Error updating post: ' . $e->getMessage());
                return $this->redirectToRoute('app_front_post_index');
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid form data',
                'errors' => $this->getFormErrors($form)
            ], 422);
        }

        return $this->render('post/_edit_form.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    private function getFormErrors($form): array
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return $errors;
    }

    #[Route('/{id}/edit-form', name: 'app_front_post_edit_form', methods: ['GET'])]
    public function editForm(Post $post): Response
    {
        $user = $this->security->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to edit a post.');
        }

        if ($user->getRole() !== 0 && $user->getMail() !== $post->getIdUser()->getMail()) {
            throw $this->createAccessDeniedException('You do not have permission to edit this post.');
        }

        $form = $this->createForm(PostEditType::class, $post);

        return $this->render('post/_edit_form.html.twig', [
            'post' => $post,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/delete', name: 'app_front_post_delete', methods: ['DELETE', 'POST'])]
    public function delete(Request $request, Post $post): Response
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to delete a post.');
        }

        if ($user->getRole() !== 0 && $user->getMail() !== $post->getIdUser()->getMail()) {
            throw $this->createAccessDeniedException('You do not have permission to delete this post.');
        }

        if ($this->isCsrfTokenValid('delete'.$post->getIdUser()->getMail(), $request->request->get('_token'))) {
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
        $user = $this->security->getUser();
<<<<<<< Updated upstream
        
        // Check if user is logged in
        if (!$user) {
            return $this->json([
                'success' => false,
                'error' => 'You must be logged in to vote'
            ], 401);
        }

        $userEmail = $user->getMail();
        $upVoteList = $post->getUpVoteList() ? explode(',', $post->getUpVoteList()) : [];
        $downVoteList = $post->getDownVoteList() ? explode(',', $post->getDownVoteList()) : [];
        
        // Check if user has already voted
        $hasUpvoted = in_array($userEmail, $upVoteList);
        $hasDownvoted = in_array($userEmail, $downVoteList);
        
        if ($type === 'up') {
            if ($hasUpvoted) {
                // User already upvoted, remove their upvote
                $upVoteList = array_diff($upVoteList, [$userEmail]);
                $post->setUpVoteList(implode(',', $upVoteList));
                $post->setVotes($post->getVotes() - 1);
            } else {
                // Remove from downvote list if exists
                if ($hasDownvoted) {
                    $downVoteList = array_diff($downVoteList, [$userEmail]);
                    $post->setDownVoteList(implode(',', $downVoteList));
                    $post->setVotes($post->getVotes() + 1); // Add back the vote that was removed
                }
                
                // Add to upvote list
                $upVoteList[] = $userEmail;
                $post->setUpVoteList(implode(',', $upVoteList));
                $post->setVotes($post->getVotes() + 1);
            }
        } else if ($type === 'down') {
            if ($hasDownvoted) {
                // User already downvoted, remove their downvote
                $downVoteList = array_diff($downVoteList, [$userEmail]);
                $post->setDownVoteList(implode(',', $downVoteList));
                $post->setVotes($post->getVotes() + 1);
            } else {
                // Remove from upvote list if exists
                if ($hasUpvoted) {
                    $upVoteList = array_diff($upVoteList, [$userEmail]);
                    $post->setUpVoteList(implode(',', $upVoteList));
                    $post->setVotes($post->getVotes() - 1); // Remove the vote that was added
                }
                
                // Add to downvote list
                $downVoteList[] = $userEmail;
                $post->setDownVoteList(implode(',', $downVoteList));
=======

        if (!$user) {
            return $this->json([
                'success' => false,
                'error' => 'You must be logged in to vote'
            ], 401);
        }

        $userEmail = $user->getMail();
        $upVoteList = $post->getUpVoteList() ?? '';
        $downVoteList = $post->getDownVoteList() ?? '';
        $upVoteEmails = $upVoteList ? explode(',', $upVoteList) : [];
        $downVoteEmails = $downVoteList ? explode(',', $downVoteList) : [];

        if ($type === 'up') {
            if (in_array($userEmail, $upVoteEmails)) {
                // Remove upvote
                $upVoteEmails = array_diff($upVoteEmails, [$userEmail]);
>>>>>>> Stashed changes
                $post->setVotes($post->getVotes() - 1);
            } else {
                // Add upvote and remove from downvotes if exists
                if (in_array($userEmail, $downVoteEmails)) {
                    $downVoteEmails = array_diff($downVoteEmails, [$userEmail]);
                    $post->setVotes($post->getVotes() + 2); // +1 for removing downvote, +1 for adding upvote
                } else {
                    $post->setVotes($post->getVotes() + 1);
                }
                $upVoteEmails[] = $userEmail;
            }
        } else if ($type === 'down') {
            if (in_array($userEmail, $downVoteEmails)) {
                // Remove downvote
                $downVoteEmails = array_diff($downVoteEmails, [$userEmail]);
                $post->setVotes($post->getVotes() + 1);
            } else {
                // Add downvote and remove from upvotes if exists
                if (in_array($userEmail, $upVoteEmails)) {
                    $upVoteEmails = array_diff($upVoteEmails, [$userEmail]);
                    $post->setVotes($post->getVotes() - 2); // -1 for removing upvote, -1 for adding downvote
                } else {
                    $post->setVotes($post->getVotes() - 1);
                }
                $downVoteEmails[] = $userEmail;
            }
        }
<<<<<<< Updated upstream
        
        try {
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true,
                'votes' => $post->getVotes(),
                'upVoteList' => $post->getUpVoteList() ?? '',
                'downVoteList' => $post->getDownVoteList() ?? ''
=======

        $post->setUpVoteList(implode(',', $upVoteEmails));
        $post->setDownVoteList(implode(',', $downVoteEmails));

        try {
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'votes' => $post->getVotes(),
                'upVoteList' => $post->getUpVoteList(),
                'downVoteList' => $post->getDownVoteList()
>>>>>>> Stashed changes
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Error updating vote: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/report', name: 'app_front_post_report', methods: ['POST'])]
    public function report(Post $post): Response
    {
        $user = $this->security->getUser();
<<<<<<< Updated upstream
        
        // Check if user is logged in
        if (!$user) {
            return $this->json([
                'success' => false,
                'error' => 'You must be logged in to report'
            ], 401);
        }

        $userEmail = $user->getMail();
        $signalList = $post->getSignalList() ? explode(',', $post->getSignalList()) : [];
        
        // Check if user has already reported
        if (!in_array($userEmail, $signalList)) {
            $signalList[] = $userEmail;
            $post->setSignalList(implode(',', $signalList));
            $post->setNbrSignal($post->getNbrSignal() + 1);
            
            try {
                $this->entityManager->flush();
                
                return $this->json([
                    'success' => true,
                    'reports' => $post->getNbrSignal(),
                    'signalList' => $post->getSignalList() ?? ''
                ]);
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'error' => 'Error updating report: ' . $e->getMessage()
                ], 500);
            }
=======

        if (!$user) {
            return $this->json([
                'success' => false,
                'error' => 'You must be logged in to report'
            ], 401);
>>>>>>> Stashed changes
        }

        $userEmail = $user->getMail();
        $signalList = $post->getSignalList() ?? '';
        $signalEmails = $signalList ? explode(',', $signalList) : [];

        if (!in_array($userEmail, $signalEmails)) {
            $signalEmails[] = $userEmail;
            $post->setSignalList(implode(',', $signalEmails));
            $post->setNbrSignal($post->getNbrSignal() + 1);

            try {
                $this->entityManager->flush();

                return $this->json([
                    'success' => true,
                    'reports' => $post->getNbrSignal(),
                    'signalList' => $post->getSignalList()
                ]);
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'error' => 'Error updating report: ' . $e->getMessage()
                ], 500);
            }
        }

        return $this->json([
            'success' => false,
            'error' => 'You have already reported this post'
        ], 400);
    }

    #[Route('/{id}/create-thread', name: 'app_front_post_create_thread', methods: ['POST'])]
    public function createThread(Post $post, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $commentText = $data['comment'] ?? 'Initial comment';

            $result = $this->disqusService->createThreadWithComment($post, $commentText);

            if ($result['success']) {
                // Store the thread ID in the session for future use
                $request->getSession()->set('disqus_thread_' . $post->getId(), $result['thread']['id']);
            }

            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/create-comment', name: 'app_front_post_create_comment', methods: ['POST'])]
    public function createComment(Post $post, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $message = $data['message'] ?? '';
            $threadId = $data['thread'] ?? $this->disqusService->getDefaultThreadId();

            if (empty($message)) {
                throw new \InvalidArgumentException('Message is required');
            }

            $result = $this->disqusService->createComment($threadId, $message);
            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/auto-comment', name: 'app_front_post_auto_comment', methods: ['POST'])]
    public function autoComment(Post $post, AutoBotCommentService $autoBotCommentService): JsonResponse
    {
        try {
            $comment = $autoBotCommentService->autoCommentOnPost($post);
<<<<<<< Updated upstream
            
=======

>>>>>>> Stashed changes
            if ($comment) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Auto-comment created successfully',
                    'comment' => [
                        'id' => $comment->getId(),
                        'content' => $comment->getContent(),
                        'dateCreation' => $comment->getDateCreation()->format('Y-m-d H:i:s')
                    ]
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Auto-comment already exists or could not be created'
                ]);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error creating auto-comment: ' . $e->getMessage()
            ], 500);
        }
    }

    private function handleFileUpload($form, Post $post, ?string $originalFile = null): void
    {

        $file = $form->get('chemin_fichier')->getData();

        if ($file) {
            if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
                throw new \InvalidArgumentException('Invalid file type');
            }

            $projectDir = $this->getParameter('kernel.project_dir');
            $uploadPath = $projectDir.self::UPLOAD_DIR;
<<<<<<< Updated upstream
            
            $newFilename = uniqid().'.'.$file->guessExtension();
            
            $file->move($uploadPath, $newFilename);
            
=======

            $newFilename = uniqid().'.'.$file->guessExtension();

            $file->move($uploadPath, $newFilename);

>>>>>>> Stashed changes
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

    #[Route('/pdf/{id}', name: 'app_front_post_pdf', methods: ['GET'])]
    public function generatePdf(Post $post, Pdf $knpSnappyPdf): Response
    {
        try {
            $html = $this->renderView('post/pdf.html.twig', [
                'post' => $post
            ]);

            $filename = sprintf('post-%d.pdf', $post->getPostId());

<<<<<<< Updated upstream
            // Add some debugging
=======
>>>>>>> Stashed changes
            $binary = $knpSnappyPdf->getBinary();
            if (!file_exists($binary)) {
                throw new \RuntimeException(sprintf(
                    'The wkhtmltopdf binary was not found at path "%s". Please check your configuration.',
                    $binary
                ));
            }

            $pdf = $knpSnappyPdf->getOutputFromHtml($html);

            return new Response(
                $pdf,
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
                ]
            );
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error generating PDF: ' . $e->getMessage());
            return $this->redirectToRoute('app_front_post_index');
        }
    }
}