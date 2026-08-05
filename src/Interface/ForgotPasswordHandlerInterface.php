<?php

namespace NandoJans\UserTools\Interface;

use DateTimeImmutable;

interface ForgotPasswordHandlerInterface
{
    /**
     * Persist the token digest (never the token itself) for the given user.
     */
    public function createResetRequest(object $user, string $tokenHash, DateTimeImmutable $expiresAt): void;

    /**
     * Atomically consume a valid, unused token digest and change the password.
     *
     * Returns false when the token is unknown, expired, or already used.
     */
    public function resetPassword(string $tokenHash, string $plainPassword): bool;
}
