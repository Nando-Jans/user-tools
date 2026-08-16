<?php

namespace NandoJans\UserTools\Tests\Form;

use NandoJans\UserTools\Entity\User;
use NandoJans\UserTools\Form\UserDetailsType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

final class UserDetailsTypeTest extends TestCase
{
    public function testItMapsAConcreteUserSubclassAsAnObject(): void
    {
        $user = (new class extends User {})
            ->setUsername('nando')
            ->setEmail('nando@example.com');

        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->addType(new UserDetailsType())
            ->getFormFactory();

        $form = $factory->create(UserDetailsType::class, $user);

        self::assertSame('nando', $form->get('username')->getData());
        self::assertSame('nando@example.com', $form->get('email')->getData());
        self::assertSame($user, $form->getData());
        self::assertTrue($form->has('password'));
    }
}
