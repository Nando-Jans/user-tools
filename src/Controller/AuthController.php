<?php

namespace NandoJans\UserTools\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use NandoJans\UserTools\Entity\User;
use NandoJans\UserTools\Form\LoginType;
use NandoJans\UserTools\Service\TwoFactorSetupService;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/auth')]
final class AuthController extends AbstractController
{
    /** @param list<string> $twoFactorDisabledEnvironments */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly TwoFactorSetupService $twoFactorSetup,
        private readonly string $environment,
        private readonly array $twoFactorDisabledEnvironments,
        private readonly string $targetRoute,
    ) {
    }

    #[Route('/login', name: 'app_auth_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('@NandoJansUserTools/auth/login.html.twig', [
            'form' => $this->createForm(LoginType::class)->createView(),
            'authenticationError' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/2fa/setup', name: '2fa_setup', methods: ['GET'])]
    public function setup(GoogleAuthenticatorInterface $authenticator): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_auth_login');
        }
        if ($this->twoFactorIsDisabled()) {
            return $this->redirectToRoute($this->targetRoute);
        }

        $session = $this->requestStack->getSession();
        $secret = $this->twoFactorSetup->getOrCreateSecret($session, $authenticator);
        $original = $user->getGoogleAuthenticatorSecret();
        try {
            $user->setGoogleAuthenticatorSecret($secret);
            $qrContent = $authenticator->getQRContent($user);
        } finally {
            $user->setGoogleAuthenticatorSecret($original);
        }

        $qrCode = (new PngWriter())->write(new QrCode(data: $qrContent, size: 250));

        return $this->render('@NandoJansUserTools/auth/2fa_setup.html.twig', [
            'qrCode' => $qrCode->getDataUri(),
            'secret' => $secret,
        ]);
    }

    #[Route('/2fa/setup/verify', name: '2fa_setup_verify', methods: ['POST'])]
    public function verify(Request $request, GoogleAuthenticatorInterface $authenticator): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_auth_login');
        }
        if ($this->twoFactorIsDisabled()) {
            return $this->redirectToRoute($this->targetRoute);
        }

        $session = $this->requestStack->getSession();
        $secret = $this->twoFactorSetup->getSecret($session);
        if (null === $secret) {
            $this->addFlash('error', 'No temporary 2FA secret was found. Please try again.');
            return $this->redirectToRoute('2fa_setup');
        }

        $code = trim((string) $request->request->get('_auth_code', ''));
        if ('' === $code) {
            $this->addFlash('error', 'Enter the verification code.');
            return $this->redirectToRoute('2fa_setup');
        }

        $original = $user->getGoogleAuthenticatorSecret();
        try {
            $user->setGoogleAuthenticatorSecret($secret);
            $isValid = $authenticator->checkCode($user, $code);
        } finally {
            $user->setGoogleAuthenticatorSecret($original);
        }

        if (!$isValid) {
            $this->addFlash('error', 'Invalid code. Please try again.');
            return $this->redirectToRoute('2fa_setup');
        }

        $user->setGoogleAuthenticatorSecret($secret);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->twoFactorSetup->clearSecret($session);
        $this->addFlash('success', '2FA has been enabled.');

        return $this->redirectToRoute($this->targetRoute);
    }

    private function twoFactorIsDisabled(): bool
    {
        return in_array($this->environment, $this->twoFactorDisabledEnvironments, true);
    }
}
