<?php

namespace NandoJans\UserTools\Interface;

use Doctrine\DBAL\LockMode;

interface UserRepositoryInterface
{
    public function findOneBy(array $criteria, array|null $orderBy = null): object|null;

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object;

    /** @return list<object> */
    public function findAll(): array;
}
