<?php

namespace NandoJans\UserTools\Controller;

use NandoJans\UserTools\Form\ForgotPasswordType;
use NandoJans\UserTools\Form\ResetPasswordType;
use NandoJans\UserTools\Interface\ForgotPasswordHandlerInterface;
use NandoJans\UserTools\Interface\PasswordForgotEmailInterface;
use NandoJans\UserTools\Interface\UserDetailsHandlerInterface;
use NandoJans\UserTools\Interface\UserRepositoryInterface;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserController extends AbstractController
{
    public function __construct(
        private readonly array              $detailsPage,
        private readonly array              $forgotPasswordPage,
        private readonly string              $loginRoute,
        private readonly UserDetailsHandlerInterface $userDetailsHandler,
        private readonly ForgotPasswordHandlerInterface $forgotPasswordHandler,
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordForgotEmailInterface $sendPasswordForgotEmail,
        private readonly UrlGeneratorInterface $urlGenerator,
    )
    {
    }

    #[Route('/user/details', name: 'nando_jans_user_tools_details')]
    public function details(Request $request): Response
    {
        $user = $this->getUser();
        $form = $this->createForm($this->detailsPage['form_type'], $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $password = $form->get('password')->getData();

            $this->userDetailsHandler->save($data, $password);

            $this->addFlash('success', $this->detailsPage['success_message']);

            return $this->redirectToRoute('nando_jans_user_tools_details');
        } else if ($form->isSubmitted() && !$form->isValid()) {

        }

        return $this->render('@NandoJansUserTools/user/details.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/auth/forgot_password', name: 'nando_jans_user_tools_forgot_password')]
    public function forgotPassword(Request $request): Response
    {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $this->userRepository->findOneBy(['email' => $email]);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiresAt = new DateTimeImmutable(sprintf(
                    '+%d seconds',
                    $this->forgotPasswordPage['token_ttl'],
                ));

                $this->forgotPasswordHandler->createResetRequest($user, $tokenHash, $expiresAt);
                $resetUrl = $this->urlGenerator->generate(
                    'nando_jans_user_tools_reset_password',
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                );
                $this->sendPasswordForgotEmail->send($user, $resetUrl);
            }

            // Use the same response for known and unknown addresses to prevent
            // account enumeration.
            $this->addFlash('success', $this->forgotPasswordPage['success_message']);

            return $this->redirectToRoute($this->loginRoute);
        }

        return $this->render('@NandoJansUserTools/auth/forgot_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/auth/reset_password/{token}',
        name: 'nando_jans_user_tools_reset_password',
        requirements: ['token' => '[a-f0-9]{64}'],
        methods: ['GET', 'POST'],
    )]
    public function resetPassword(Request $request, string $token): Response
    {
        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->get('password')->getData();
            $wasReset = $this->forgotPasswordHandler->resetPassword(
                hash('sha256', $token),
                $password,
            );

            if ($wasReset) {
                $this->addFlash('success', $this->forgotPasswordPage['reset_success_message']);

                return $this->redirectToRoute($this->loginRoute);
            }

            $this->addFlash('error', $this->forgotPasswordPage['invalid_token_message']);
        }

        $response = $this->render('@NandoJansUserTools/auth/reset_password.html.twig', [
            'form' => $form->createView(),
        ]);

        // Keep the bearer token out of caches and Referer headers.
        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
