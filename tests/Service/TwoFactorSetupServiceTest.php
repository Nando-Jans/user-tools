<?php

namespace NandoJans\UserTools\Tests\Service;

use NandoJans\UserTools\Service\TwoFactorSetupService;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class TwoFactorSetupServiceTest extends TestCase
{
    public function testItCreatesAndReusesATemporarySecret(): void
    {
        $authenticator = $this->createMock(GoogleAuthenticatorInterface::class);
        $authenticator->expects(self::once())->method('generateSecret')->willReturn('secret');
        $session = new Session(new MockArraySessionStorage());
        $service = new TwoFactorSetupService();

        self::assertSame('secret', $service->getOrCreateSecret($session, $authenticator));
        self::assertSame('secret', $service->getOrCreateSecret($session, $authenticator));
    }

    public function testItClearsTheTemporarySecret(): void
    {
        $authenticator = $this->createStub(GoogleAuthenticatorInterface::class);
        $authenticator->method('generateSecret')->willReturn('secret');
        $session = new Session(new MockArraySessionStorage());
        $service = new TwoFactorSetupService();
        $service->getOrCreateSecret($session, $authenticator);

        $service->clearSecret($session);

        self::assertNull($service->getSecret($session));
    }
}
