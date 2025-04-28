<?php

namespace App\Controller;

use App\Entity\Todo;
use App\Repository\TodoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TodoController extends AbstractController
{
    #[Route('/todo/add', name: 'app_todo_add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $title = $request->request->get('title');

        if ($title) {
            $todo = new Todo();
            $todo->setTitle($title);
            $todo->setStatus(false);
            $todo->setCreatedAt(new \DateTimeImmutable());

            $em->persist($todo);
            $em->flush();
        }

        return $this->redirectToRoute('app_dash_board');
    }

    #[Route('/todo/done/{id}', name: 'app_todo_done')]
    public function markAsDone(Todo $todo, EntityManagerInterface $em): Response
    {
        $todo->setStatus(true);
        $todo->setUpdatedAt(new \DateTimeImmutable());

        $em->flush();

        return $this->redirectToRoute('app_dash_board');
    }

    #[Route('/todo/delete/{id}', name: 'app_todo_delete')]
    public function delete(Todo $todo, EntityManagerInterface $em): Response
    {
        $em->remove($todo);
        $em->flush();

        return $this->redirectToRoute('app_dash_board');
    }
}
