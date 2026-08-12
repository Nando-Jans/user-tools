<?php

namespace NandoJans\UserTools\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'password_reset_request')]
class PasswordResetRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private UserInterface $user;

    #[ORM\Column(length: 64, unique: true)]
    private string $tokenHash;

    #[ORM\Column]
    private DateTimeImmutable $expiresAt;

    public function __construct(User $user, string $tokenHash, DateTimeImmutable $expiresAt)
    {
        $this->user = $user;
        $this->tokenHash = $tokenHash;
        $this->expiresAt = $expiresAt;
    }

    public function getUser(): User
    {
        if (!$this->user instanceof User) {
            throw new \LogicException('The configured user entity must extend the user-tools User entity.');
        }

        return $this->user;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
