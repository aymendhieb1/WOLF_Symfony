<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class VerifyCodeController extends AbstractController
{
    #[Route('/verifier-code', name: 'app_code_check')]
    public function verifyCode(Request $request, SessionInterface $session): Response
    {
        $storedCode = $session->get('reset_code');

        if (!$storedCode) {
            $this->addFlash('danger', 'Session expirée ou code manquant.');
            return $this->redirectToRoute('app_reset_password');
        }

        if ($request->isMethod('POST')) {
            $enteredCode = $request->request->get('code');

            if ($enteredCode == $storedCode) {
                return $this->redirectToRoute('app_reset_code_verification');
            }

            $this->addFlash('danger', 'Code incorrect.');
        }

        return $this->render('verify_code/index.html.twig');
    }

}
