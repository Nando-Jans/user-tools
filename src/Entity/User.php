<?php

namespace NandoJans\UserTools\Entity;

use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\MappedSuperclass]
abstract class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    public const string ROLE_USER = 'ROLE_USER';
    public const string ROLE_ADMIN = 'ROLE_ADMIN';
    public const array ROLES = [self::ROLE_USER, self::ROLE_ADMIN];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    protected ?string $email = null;

    /** @var list<string> */
    #[ORM\Column]
    #[Assert\Count(min: 1)]
    protected array $roles = [];

    #[ORM\Column]
    #[Assert\DisableAutoMapping]
    protected ?string $password = null;

    #[Assert\NotBlank(groups: ['create'])]
    #[Assert\Length(min: 8)]
    #[Assert\NotCompromisedPassword]
    protected ?string $plainPassword = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3)]
    protected ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $googleAuthenticatorSecret = null;

    public function getId(): ?int { return $this->id; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }
    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, self::ROLE_USER]));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }
    public function getPlainPassword(): ?string { return $this->plainPassword; }
    public function setPlainPassword(?string $plainPassword): static { $this->plainPassword = $plainPassword; return $this; }
    public function getUsername(): ?string { return $this->username; }
    public function setUsername(string $username): static { $this->username = $username; return $this; }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', (string) $this->password);
        return $data;
    }

    public function isGoogleAuthenticatorEnabled(): bool
    {
        return is_string($this->googleAuthenticatorSecret) && '' !== trim($this->googleAuthenticatorSecret);
    }

    public function getGoogleAuthenticatorUsername(): ?string { return $this->username; }
    public function getGoogleAuthenticatorSecret(): ?string { return $this->googleAuthenticatorSecret; }
    public function setGoogleAuthenticatorSecret(?string $secret): void { $this->googleAuthenticatorSecret = $secret; }
    public function isGranted(string $role): bool { return in_array($role, $this->getRoles(), true); }
    public function __toString(): string { return $this->email ?? ''; }
}
