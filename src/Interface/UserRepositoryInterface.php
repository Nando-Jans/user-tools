<?php

namespace NandoJans\UserTools\Interface;

interface UserRepositoryInterface
{
    public function findOneBy(array $criteria, array|null $orderBy = null): object|null;
}