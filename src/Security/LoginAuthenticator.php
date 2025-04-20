<?php
namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class LoginAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;

    private RouterInterface $router;
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;
    private UserPasswordHasherInterface $passwordHasher;



    public function __construct(
        RouterInterface $router,
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->router = $router;
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->passwordHasher = $passwordHasher;


    }
    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'app_seconnecter' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $mail = $request->request->get('mail');
        $password = $request->request->get('mdp');
        $captchaResponse = $request->request->get('h-captcha-response');

        $session = $this->requestStack->getSession();

        if (!$captchaResponse) {
            $session?->getFlashBag()->add('warning', 'Veuillez valider le captcha.');
            throw new CustomUserMessageAuthenticationException('Captcha requis');
        }

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', 'https://hcaptcha.com/siteverify', [
                'form_params' => [
                    'secret' => 'ed883902-a6d6-424a-978a-1a5e36147308',
                    'response' => $captchaResponse,
                    'remoteip' => $request->getClientIp(),
                ],
            ]);

            $captchaData = json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            $session?->getFlashBag()->add('warning', 'Erreur de validation du captcha.');
            throw new CustomUserMessageAuthenticationException('Erreur hCaptcha');
        }



        $user = $this->entityManager->getRepository(User::class)->findOneBy(['mail' => $mail]);

        if (!$user) {
            $session?->getFlashBag()->add('warning', 'Aucun compte ne correspond à cet email.');
            throw new CustomUserMessageAuthenticationException('INVALID_EMAIL');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            $session?->getFlashBag()->add('warning', 'Mot de passe incorrect.');
            throw new CustomUserMessageAuthenticationException('INVALID_PASSWORD');
        }

        return new Passport(
            new UserBadge($mail),
            new PasswordCredentials($password)
        );
    }




    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): RedirectResponse
    {
        $user = $token->getUser();
        $roles = $user->getRoles();

        if (in_array('ROLE_CLIENT', $roles, true)) {
            return new RedirectResponse($this->router->generate('app_home'));
        }

        return new RedirectResponse($this->router->generate('app_back_office'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse
    {
        if ($exception->getMessageKey() === 'USER_BLOCKED') {
            $session = $this->requestStack->getSession();
            if ($session) {
                $session->getFlashBag()->add('warning
                ', 'Votre compte est bloqué. Veuillez contacter l\'administrateur.');
            }

        }

        return new RedirectResponse($this->router->generate('app_seconnecter'));
    }



}