<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET'])]
    public function contactPage(): Response
    {
        return $this->render('contact/index.html.twig');
    }

    #[Route('/contact/send', name: 'contact_send', methods: ['POST'])]
    public function sendMessage(Request $request, MailerInterface $mailer): Response
    {
        $prenom = $request->request->get('fname');
        $nom = $request->request->get('lname');
        $emailUser = $request->request->get('email');
        $phone = $request->request->get('phone');
        $messageContent = $request->request->get('message');

        $email = (new Email())
            ->from($emailUser)
            ->to('triptogo2025@gmail.com')
            ->subject('Nouveau message de contact TripToGo')
            ->text("Prénom: $prenom\nNom: $nom\nEmail: $emailUser\nTéléphone: $phone\n\nMessage:\n$messageContent");

        $mailer->send($email);
        $session = $request->getSession();
        $notifications = $session->get('notifications', []);
        $notifications[] = [
            'title' => 'Nouveau message de contact',
            'text' => "Message de $prenom $nom",
            'time' => (new \DateTime())->format('H:i'),
        ];
        $session->set('notifications', $notifications);

        $this->addFlash('success', 'Votre message a été envoyé avec succès !');
        return $this->redirectToRoute('app_contact');
    }
}


