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

class GoogleAuthenticator extends AbstractAuthenticator
{
    private GoogleClient $client;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;
    private RequestStack $requestStack;

    public function __construct(GoogleClient $client, EntityManagerInterface $entityManager, RouterInterface $router, RequestStack $requestStack)
    {
        $this->client = $client;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->requestStack = $requestStack;
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
                return $this->getOrCreateUser($googleUser);
            }),
            [new RememberMeBadge()]
        );
    }

    private function getOrCreateUser(GoogleUser $googleUser): User
    {
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['mail' => $googleUser->getEmail()]);

        if (!$existingUser) {
            $existingUser = new User();
            $existingUser->setMail($googleUser->getEmail());
            $existingUser->setMdp(bin2hex(random_bytes(16)));

            $nomComplet = $googleUser->getName();
            $prenom = $googleUser->getFirstName();
            $nom = $googleUser->getLastName();
            $photo_profil = $googleUser->getAvatar();
            if (preg_match('/=s\d+-c$/', $photo_profil)) {
                $highResAvatar = preg_replace('/=s\d+-c$/', '=s800-c', $photo_profil);
            } else {
                $highResAvatar = $photo_profil . '=s800-c';
            }

            if (!$prenom || !$nom) {
                $parts = explode(' ', $nomComplet, 2);
                $prenom = $parts[0] ?? 'Utilisateur';
                $nom = $parts[1] ?? 'Google';
            }
            $existingUser->setPrenom($prenom);
            $existingUser->setNom($nom);
            $existingUser->setRole(2);
            $existingUser->setStatus(0);
            $existingUser->setPhotoProfil($highResAvatar);



            $this->entityManager->persist($existingUser);
            $this->entityManager->flush();
        }

        // Vérification du statut
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