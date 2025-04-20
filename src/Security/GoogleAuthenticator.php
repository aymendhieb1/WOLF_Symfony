<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;


class GoogleAuthenticator extends AbstractAuthenticator
{
    private GoogleClient $client;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;
    private RequestStack $requestStack;
    private UserPasswordHasherInterface $passwordHasher;
    private MailerInterface $mailer;



    public function __construct(
        GoogleClient $client,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        RequestStack $requestStack,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer
    ) {
        $this->client = $client;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->passwordHasher = $passwordHasher;
        $this->mailer = $mailer;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $accessToken = $this->client->getAccessToken();
        $googleUser = $this->client->fetchUserFromToken($accessToken);
        $email = $googleUser->getEmail();

        return new SelfValidatingPassport(
            new UserBadge($email, function () use ($googleUser) {
                return $this->getOrCreateUser($googleUser, $this->passwordHasher, $this->mailer);
            }),
            [new RememberMeBadge()]
        );

    }

    private function getOrCreateUser(GoogleUser $googleUser, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer): User
    {
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['mail' => $googleUser->getEmail()]);

        if (!$existingUser) {
            $existingUser = new User();
            $existingUser->setMail($googleUser->getEmail());

            $nomComplet = $googleUser->getName();
            $prenom = $googleUser->getFirstName();
            $nom = $googleUser->getLastName();
            $photo_profil = $googleUser->getAvatar();
            $highResAvatar = preg_match('/=s\d+-c$/', $photo_profil)
                ? preg_replace('/=s\d+-c$/', '=s800-c', $photo_profil)
                : $photo_profil . '=s800-c';

            if (!$prenom || !$nom) {
                $parts = explode(' ', $nomComplet, 2);
                $prenom = $parts[0] ?? 'Utilisateur';
                $nom = $parts[1] ?? 'Google';
            }

            // ✅ Génération d’un mot de passe temporaire
            $tempPassword = bin2hex(random_bytes(4)); // Exemple : 8 caractères aléatoires
            $hashedPassword = $passwordHasher->hashPassword($existingUser, $tempPassword);
            $existingUser->setMdp($hashedPassword);

            $existingUser->setPrenom($prenom);
            $existingUser->setNom($nom);
            $existingUser->setRole(2);
            $existingUser->setStatus(0);
            $existingUser->setPhotoProfil($highResAvatar);

            $this->entityManager->persist($existingUser);
            $this->entityManager->flush();

            $email = (new Email())
                ->from('triptogo2025@gmail.com')
                ->to($existingUser->getMail())
                ->subject('Bienvenue sur TripToGo 🚀')
                ->html('
                <div style="font-family: Arial, sans-serif; background-color: #f4f7fa; padding: 40px;">
                    <div style="max-width: 600px; margin: auto; background-color: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.1);">
                        <div style="text-align: center; margin-bottom: 30px;">
                            <img src="cid:logo" alt="TripToGo Logo" style="width: 120px; height: auto;">
                        </div>
                        <h2 style="color: #132a3e; font-size: 24px; text-align: center; margin-bottom: 10px;">
                            Bienvenue sur <span style="color: #ff681a;">TripToGo</span> !
                        </h2>
                        <p style="font-size: 16px; color: #444; line-height: 1.6; text-align: center;">
                            Bonjour <strong>' . htmlspecialchars($prenom) . '</strong>,<br>
                            Merci d’avoir créé un compte via Google.
                        </p>
                        <p style="font-size: 16px; color: #444; text-align: center;">
                            🔐 <strong>Mot de passe temporaire :</strong><br>
                            <span style="color: #ff681a; font-weight: bold; font-size: 18px;">' . htmlspecialchars($tempPassword) . '</span>
                        </p>
                        <p style="font-size: 15px; color: #555; text-align: center;">
                            Veuillez changer ce mot de passe dès votre première connexion pour sécuriser votre compte.
                        </p>
                        <div style="text-align: center; margin: 30px 0;">
                            <a href="http://127.0.0.1:8000/seconnecter" style="background-color: #ff681a; color: #fff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: bold; font-size: 16px;">
                                Se connecter
                            </a>
                        </div>
                        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                        <p style="font-size: 12px; color: #999; text-align: center;">
                            © ' . date("Y") . ' TripToGo. Tous droits réservés.
                        </p>
                    </div>
                </div>
            ')->embedFromPath('C:/Users/Dhib/IdeaProjects/Projet_Pidev/src/main/resources/images/primary.png', 'logo');



            $mailer->send($email);
        }

        if ($existingUser->getStatus() === 1) {
            throw new CustomUserMessageAuthenticationException('USER_BLOCKED');
        }

        return $existingUser;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();



        // Vérifie si l'utilisateur a un rôle admin ou client
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return new RedirectResponse($this->router->generate('app_back_office'));
        }

        if (in_array('ROLE_CLIENT', $user->getRoles())) {
            return new RedirectResponse($this->router->generate('app_home'));
        }

        // Par défaut, si aucun rôle ne correspond, redirige vers la page de connexion
        return new RedirectResponse($this->router->generate('app_seconnecter'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($exception->getMessageKey() === 'USER_BLOCKED') {
            $session = $this->requestStack->getSession();
            if ($session) {
                $session->set('blocked_message', 'Votre compte est bloqué. Veuillez contacter l\'administrateur.');
            }
        }

        return new RedirectResponse($this->router->generate('app_seconnecter'));
    }
}