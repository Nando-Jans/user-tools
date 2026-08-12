<?php

namespace NandoJans\UserTools\Security;

use NandoJans\UserTools\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

final class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    /** @param list<string> $twoFactorDisabledEnvironments */
    public function __construct(
        private readonly RouterInterface $router,
        private readonly string $loginRoute,
        private readonly string $targetRoute,
        private readonly string $twoFactorSetupRoute,
        private readonly string $environment,
        private readonly array $twoFactorDisabledEnvironments,
    ) {
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate($this->loginRoute);
    }

    public function authenticate(Request $request): Passport
    {
        $data = $request->request->all('login');

        return new Passport(
            new UserBadge((string) ($data['email'] ?? '')),
            new PasswordCredentials((string) ($data['password'] ?? '')),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($user instanceof User
            && !in_array($this->environment, $this->twoFactorDisabledEnvironments, true)
            && !$user->isGoogleAuthenticatorEnabled()
        ) {
            return new RedirectResponse($this->router->generate($this->twoFactorSetupRoute));
        }

        return new RedirectResponse($this->router->generate($this->targetRoute));
    }
}
