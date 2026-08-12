<?php

namespace NandoJans\UserTools\Email;

use NandoJans\UserTools\Entity\User;
use NandoJans\UserTools\Interface\PasswordForgotEmailInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class PasswordForgotEmail implements PasswordForgotEmailInterface
{
    /** @param array{from_address: string, from_name: string, subject: string} $config */
    public function __construct(private readonly MailerInterface $mailer, private readonly array $config)
    {
    }

    public function send(object $user, string $resetUrl): void
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException(sprintf('Expected an instance of %s.', User::class));
        }

        $username = htmlspecialchars($user->getUsername() ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $url = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = <<<HTML
<p>Hello {$username},</p>
<p>You requested a new password. Use the link below to choose one:</p>
<p><a href="{$url}">Reset password</a></p>
<p>This link expires and can only be used once. If you did not request it, you can ignore this e-mail.</p>
HTML;

        $email = (new Email())
            ->from(new Address($this->config['from_address'], $this->config['from_name']))
            ->to((string) $user->getEmail())
            ->subject($this->config['subject'])
            ->html($html)
            ->text(sprintf("Hello %s,\n\nReset your password: %s", $user->getUsername(), $resetUrl));

        $this->mailer->send($email);
    }
}
