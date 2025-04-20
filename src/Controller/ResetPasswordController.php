<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
class ResetPasswordController extends AbstractController
{
    #[Route('/reset/password', name: 'app_reset_password')]
    public function index(Request $request, UserRepository $userRepo, MailerInterface $mailer, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $userRepo->findOneBy(['mail' => $email]);

            if ($user) {
                $code = random_int(100000, 999999);

                $request->getSession()->set('reset_code', $code);
                $request->getSession()->set('reset_email', $email);
                $verificationUrl = $this->generateUrl('app_code_check', [], UrlGeneratorInterface::ABSOLUTE_URL);


                // Envoyer le code par email
                $emailMessage = (new Email())
                    ->from('triptogo2025@gmail.com')
                    ->to($user->getMail())
                    ->subject('Code de réinitialisation de votre mot de passe')
                    ->html('
                    <div style="font-family: Arial, sans-serif; background-color: #f4f7fa; padding: 30px;">
    <div style="max-width: 600px; margin: auto; background-color: #ffffff; border-radius: 10px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="cid:logo" alt="TripToGo Logo" style="width: 120px; height: auto;">
        </div>
        <h2 style="color: #333333; text-align: center;">Réinitialisation du mot de passe</h2>
        <p style="font-size: 16px; color: #555555; text-align: center;">
            Bonjour, <br><br>
            Vous avez demandé à réinitialiser votre mot de passe. Voici votre code de vérification :
        </p>
        <div style="text-align: center; margin: 30px 0;">
            <span style="font-size: 32px; color: #ff681a; font-weight: bold;">' . $code . '</span>
        </div>
        <div style="text-align: center; margin-bottom: 20px;">
            <a href="' . $verificationUrl . '" style="display: inline-block; padding: 12px 24px; background-color: #ff681a; color: white; border-radius: 6px; text-decoration: none; font-weight: bold;">
                👉 Saisir le code maintenant
            </a>
        </div>
        <p style="font-size: 14px; color: #999999; text-align: center;">
            Ce code est valable pendant quelques minutes. Si vous n’êtes pas à l’origine de cette demande, ignorez cet email.
        </p>
        <hr style="border: none; border-top: 1px solid #eeeeee; margin: 30px 0;">
        <p style="font-size: 12px; color: #aaaaaa; text-align: center;">
            © ' . date("Y") . ' TripToGo. Tous droits réservés.
        </p>
    </div>
</div>')
                    ->embedFromPath('C:/Users/Dhib/IdeaProjects/Projet_Pidev/src/main/resources/images/primary.png', 'logo');


                $mailer->send($emailMessage);

                $this->addFlash('success', 'Un code de réinitialisation vous a été envoyé par email.');
            } else {
                $this->addFlash('danger', 'Adresse e-mail introuvable.');
            }
        }

        return $this->render('reset_password/index.html.twig');
    }
}
