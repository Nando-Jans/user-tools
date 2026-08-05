<?php

namespace NandoJans\UserTools\Interface;

interface PasswordForgotEmailInterface
{
    public function send(object $user, string $resetUrl): void;
}
