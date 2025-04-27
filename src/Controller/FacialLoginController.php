<?php


namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Security;
use App\Entity\User;

class FacialLoginController extends AbstractController
{
    #[Route('/facial-auth', name: 'facial_auth')]
    public function facialAuth(AuthenticationManagerInterface $authenticationManager): JsonResponse
    {
        $pythonScript = 'C:\Users\Dhib\Desktop\face_id_test\login.py';

        $pythonPath = '"C:\Program Files\Python311\python.exe"';

        $command = $pythonPath . ' ' . escapeshellarg($pythonScript);

        exec($command, $output, $returnCode);



        if ($returnCode === 0 && in_array("True", $output)) {
            $user = $this->getUserFromFacialRecognition();

            if ($user) {
                // Authentifier l'utilisateur avec Symfony
                $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                $authenticationManager->authenticate($token);

                // Stocker le token dans Symfony
                $this->get('security.token_storage')->setToken($token);

                // Rediriger vers la page administrateur (par exemple, /back/user)
                return new JsonResponse(['success' => true, 'redirect' => $this->generateUrl('app_back_office')]);
            }

            // Si aucun utilisateur n'a été trouvé (visage non reconnu ou autre problème)
            return new JsonResponse(['success' => false, 'output' => 'Visage non reconnu']);
        }

        // Si la reconnaissance faciale échoue
        return new JsonResponse([
            'success' => false,
            'output' => $output,
            'returnCode' => $returnCode
        ], 500);
    }

    private function getUserFromFacialRecognition()
    {
        // Ici, tu utilises ta méthode de reconnaissance faciale pour récupérer l'utilisateur
        // Exemple fictif : on suppose que l'utilisateur est authentifié après la reconnaissance
        return $this->getDoctrine()->getRepository(User::class)->findOneBy(['mail' => 'triptogo2025@gmail.com']); // Ex: l'email de l'admin
    }
}


