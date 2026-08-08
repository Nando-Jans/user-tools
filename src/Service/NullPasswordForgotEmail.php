<?php

namespace NandoJans\UserTools\Service;

use NandoJans\UserTools\Interface\PasswordForgotEmailInterface;

/**
 * Safe default for applications that do not enable password-reset mail yet.
 * Override PasswordForgotEmailInterface in the application's service config.
 */
final class NullPasswordForgotEmail implements PasswordForgotEmailInterface
{
    public function send(object $user, string $resetUrl): void
    {
    }
}
