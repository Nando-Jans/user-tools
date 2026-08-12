<?php

namespace NandoJans\UserTools\Service;

use Doctrine\ORM\EntityManagerInterface;
use NandoJans\UserTools\Entity\User;
use NandoJans\UserTools\Interface\UserDetailsHandlerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserDetailsHandler implements UserDetailsHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function save(object $user, string $plainPassword): void
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException(sprintf('Expected an instance of %s.', User::class));
        }
        if ('' !== $plainPassword) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        }
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
