<?php

namespace NandoJans\UserTools\Interface;

interface UserDetailsHandlerInterface
{
    public function save(object $user, string $plainPassword): void;
}