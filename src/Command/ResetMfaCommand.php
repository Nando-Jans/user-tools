<?php

declare(strict_types=1);

namespace NandoJans\UserTools\Command;

use Doctrine\ORM\EntityManagerInterface;
use NandoJans\UserTools\Entity\User;
use NandoJans\UserTools\Interface\UserRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:reset-mfa',
    description: 'Reset MFA for a user so it can be configured again on the next login.',
)]
final class ResetMfaCommand extends Command
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::OPTIONAL, 'The email address of the user.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = trim((string) ($input->getArgument('email') ?? ''));

        if ('' === $email) {
            $email = trim((string) $io->ask('For which email address should MFA be reset?'));
        }

        if ('' === $email) {
            $io->error('An email address is required.');

            return Command::INVALID;
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            $io->error(sprintf('No user with email address "%s" was found.', $email));

            return Command::FAILURE;
        }

        if (!$user->isGoogleAuthenticatorEnabled()) {
            $io->success(sprintf('MFA is already disabled for "%s".', $email));

            return Command::SUCCESS;
        }

        $user->setGoogleAuthenticatorSecret(null);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf(
            'MFA was reset for "%s". The user will be asked to configure it on the next login.',
            $email,
        ));

        return Command::SUCCESS;
    }
}
