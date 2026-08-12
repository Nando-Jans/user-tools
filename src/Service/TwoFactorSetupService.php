<?php

namespace NandoJans\UserTools\Service;

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class TwoFactorSetupService
{
    private const SESSION_KEY = 'nando_jans_user_tools.2fa_temp_secret';

    public function getOrCreateSecret(SessionInterface $session, GoogleAuthenticatorInterface $authenticator): string
    {
        $secret = $this->getSecret($session);
        if (null !== $secret) {
            return $secret;
        }

        $secret = $authenticator->generateSecret();
        $session->set(self::SESSION_KEY, $secret);

        return $secret;
    }

    public function getSecret(SessionInterface $session): ?string
    {
        $secret = $session->get(self::SESSION_KEY);

        return is_string($secret) && '' !== $secret ? $secret : null;
    }

    public function clearSecret(SessionInterface $session): void
    {
        $session->remove(self::SESSION_KEY);
    }
}
