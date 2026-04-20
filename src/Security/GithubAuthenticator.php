<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GithubAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return 'connect_github_check' === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('github');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GithubResourceOwner $githubUser */
                $githubUser = $client->fetchUserFromToken($accessToken);

                $email = $githubUser->getEmail();

                // GitHub peut retourner un email null si privé
                if (!$email) {
                    throw new AuthenticationException('Aucune adresse email publique sur votre compte GitHub. Rendez-la publique ou utilisez une autre méthode de connexion.');
                }

                // 1. L'utilisateur existe déjà → on le connecte
                $existingUser = $this->entityManager
                    ->getRepository(User::class)
                    ->findOneBy(['email' => $email]);

                if ($existingUser) {
                    return $existingUser;
                }

                // 2. Nouvel utilisateur → on le crée
                $name = explode(' ', $githubUser->getName() ?? '', 2);

                $user = new User();
                $user->setEmail($email);
                $user->setFirstName('' !== $name[0] ? $name[0] : ($githubUser->getNickname() ?? ''));
                $user->setLastName($name[1] ?? '');
                $user->setRoles(['ROLE_USER']);
                $user->setIsVerified(true);
                $user->setPassword('');

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->router->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->set('github_auth_error', $exception->getMessage());

        return new RedirectResponse($this->router->generate('app_login'));
    }
}
