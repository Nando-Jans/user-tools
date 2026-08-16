<?php

declare(strict_types=1);

namespace NandoJans\UserTools\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use NandoJans\UserTools\Command\ResetMfaCommand;
use NandoJans\UserTools\Entity\User;
use NandoJans\UserTools\Interface\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ResetMfaCommandTest extends TestCase
{
    public function testItResetsMfaForAUser(): void
    {
        $user = new class extends User {};
        $user->setGoogleAuthenticatorSecret('secret');

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'user@example.com'])
            ->willReturn($user);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with($user);
        $entityManager->expects(self::once())->method('flush');

        $tester = new CommandTester(new ResetMfaCommand($repository, $entityManager));

        self::assertSame(Command::SUCCESS, $tester->execute(['email' => 'user@example.com']));
        self::assertFalse($user->isGoogleAuthenticatorEnabled());
        self::assertStringContainsString('MFA was reset', $tester->getDisplay());
    }

    public function testItFailsForAnUnknownUser(): void
    {
        $repository = $this->createStub(UserRepositoryInterface::class);
        $repository->method('findOneBy')->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        $tester = new CommandTester(new ResetMfaCommand($repository, $entityManager));

        self::assertSame(Command::FAILURE, $tester->execute(['email' => 'missing@example.com']));
        self::assertStringContainsString('No user', $tester->getDisplay());
    }
}
