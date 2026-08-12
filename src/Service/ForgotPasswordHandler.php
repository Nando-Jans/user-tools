<?php

namespace NandoJans\UserTools\Service;

use DateTimeImmutable;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use NandoJans\UserTools\Entity\PasswordResetRequest;
use NandoJans\UserTools\Entity\User;
use NandoJans\UserTools\Interface\ForgotPasswordHandlerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ForgotPasswordHandler implements ForgotPasswordHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function createResetRequest(object $user, string $tokenHash, DateTimeImmutable $expiresAt): void
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException(sprintf('Expected an instance of %s.', User::class));
        }

        $this->entityManager->createQueryBuilder()
            ->delete(PasswordResetRequest::class, 'request')
            ->where('request.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
        $this->entityManager->persist(new PasswordResetRequest($user, $tokenHash, $expiresAt));
        $this->entityManager->flush();
    }

    public function resetPassword(string $tokenHash, string $plainPassword): bool
    {
        return $this->entityManager->wrapInTransaction(function () use ($tokenHash, $plainPassword): bool {
            $request = $this->entityManager->createQueryBuilder()
                ->select('request', 'user')
                ->from(PasswordResetRequest::class, 'request')
                ->join('request.user', 'user')
                ->where('request.tokenHash = :tokenHash')
                ->setParameter('tokenHash', $tokenHash)
                ->getQuery()
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult();

            if (!$request instanceof PasswordResetRequest || $request->getExpiresAt() <= new DateTimeImmutable()) {
                if ($request instanceof PasswordResetRequest) {
                    $this->entityManager->remove($request);
                }
                return false;
            }

            $user = $request->getUser();
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            foreach ($this->entityManager->getRepository(PasswordResetRequest::class)->findBy(['user' => $user]) as $pending) {
                $this->entityManager->remove($pending);
            }

            return true;
        });
    }
}
