<?php

namespace NandoJans\UserTools\EventSubscriber;

use NandoJans\UserTools\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ForceTwoFactorSetupSubscriber implements EventSubscriberInterface
{
    /** @param list<string> $disabledEnvironments @param list<string> $allowedRoutes */
    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $environment,
        private readonly array $disabledEnvironments,
        private readonly string $setupRoute,
        private readonly array $allowedRoutes,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || in_array($this->environment, $this->disabledEnvironments, true)) {
            return;
        }

        $request = $event->getRequest();
        if (preg_match('#^/(?:_profiler|_wdt|assets|build)/#', $request->getPathInfo())) {
            return;
        }

        if (in_array((string) $request->attributes->get('_route'), $this->allowedRoutes, true)) {
            return;
        }

        $user = $this->security->getUser();
        if ($user instanceof User && !$user->isGoogleAuthenticatorEnabled()) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate($this->setupRoute)));
        }
    }
}
