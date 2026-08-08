<?php

namespace NandoJans\UserTools;

use NandoJans\UserTools\Controller\UserController;
use NandoJans\UserTools\Entity\User;
use NandoJans\UserTools\Form\ForgotPasswordType;
use NandoJans\UserTools\Form\ResetPasswordType;
use NandoJans\UserTools\Interface\UserRepositoryInterface;
use NandoJans\UserTools\Repository\UserRepository;
use NandoJans\UserTools\Interface\PasswordForgotEmailInterface;
use NandoJans\UserTools\Service\NullPasswordForgotEmail;
use NandoJans\UserTools\Twig\UserToolsExtension;
use NandoJans\UserTools\Twig\UserToolsViewConfig;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class NandoJansUserToolsBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('entity_class')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifTrue(static fn (string $class): bool => !is_a($class, User::class, true))
                        ->thenInvalid('The user entity must extend "%s".', User::class)
                    ->end()
                ->end()
                ->scalarNode('repository_class')
                    ->defaultValue(UserRepository::class)
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifTrue(static fn (string $class): bool => !is_a($class, UserRepositoryInterface::class, true))
                        ->thenInvalid('The user repository must implement "%s".', UserRepositoryInterface::class)
                    ->end()
                ->end()
                ->scalarNode('controller_class')
                    ->defaultValue(UserController::class)
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifTrue(static fn (string $class): bool => !is_a($class, UserController::class, true))
                        ->thenInvalid('The user controller must extend "%s".', UserController::class)
                    ->end()
                ->end()
                ->arrayNode('nav_button')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('class')
                            ->defaultValue('btn btn-link')
                        ->end()
                        ->scalarNode('icon')
                            ->defaultValue('fa fa-user')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('logout')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('class')
                            ->defaultValue('btn-logout')
                        ->end()
                        ->scalarNode('icon')
                            ->defaultValue('fa fa-sign-out')
                        ->end()
                        ->scalarNode('label')
                            ->defaultValue('Logout')
                        ->end()
                        ->scalarNode('route')
                            ->defaultValue('_logout_main')
                        ->end()
                        ->arrayNode('routeParameters')
                            ->variablePrototype()->end()
                            ->defaultValue([])
                        ->end()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('details')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('class')
                            ->defaultValue('btn-details')
                        ->end()
                        ->scalarNode('icon')
                            ->defaultValue('fa fa-list')
                        ->end()
                        ->scalarNode('label')
                            ->defaultValue('Details')
                        ->end()
                        ->scalarNode('route')
                            ->defaultValue('nando_jans_user_tools_details')
                        ->end()
                        ->arrayNode('routeParameters')
                            ->variablePrototype()->end()
                            ->defaultValue([])
                        ->end()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('items')
                    ->arrayPrototype()
                    ->children()
                        ->scalarNode('class')
                            ->defaultValue('dropdown-item')
                        ->end()
                        ->scalarNode('icon')
                            ->defaultNull()
                            ->end()
                        ->scalarNode('label')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('route')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->arrayNode('routeParameters')
                            ->variablePrototype()
                            ->end()
                            ->defaultValue([])
                            ->end()
                            ->scalarNode('role')
                                ->defaultNull()
                            ->end()
                            ->booleanNode('enabled')
                                ->defaultTrue()
                            ->end()
                        ->end()
                    ->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('details_page')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('form_type')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->validate()
                                ->ifTrue(static fn (string $class): bool =>
                                !is_a($class, FormTypeInterface::class, true)
                                )
                                ->thenInvalid(
                                    'The details_type class must implement "%s".',
                                    FormTypeInterface::class,
                                )
                            ->end()
                        ->end()
                        ->scalarNode('success_message')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('forgot_password_page')
                    ->addDefaultsIfNotSet()
                    ->children()
//                        ->scalarNode('form_type')
//                            ->isRequired()
//                            ->cannotBeEmpty()
//                                ->validate()
//                                ->ifTrue(static fn (string $class): bool =>
//                                !is_a($class, FormTypeInterface::class, true)
//                                )
//                                ->thenInvalid(
//                                    'The details_type class must implement "%s".',
//                                    FormTypeInterface::class,
//                                )
//                            ->end()
//                        ->end()
                        ->scalarNode('success_message')
                            ->defaultValue('If an account exists for that address, a password reset link has been sent.')
                        ->end()
                        ->scalarNode('user_not_found_message')
                            ->defaultValue('A user with the given e-mail adres could not be found.')
                        ->end()
                        ->scalarNode('email_label')
                            ->defaultValue('Please provide your email address.')
                        ->end()
                        ->integerNode('token_ttl')
                            ->min(60)
                            ->defaultValue(3600)
                        ->end()
                        ->scalarNode('password_label')
                            ->defaultValue('New password')
                        ->end()
                        ->scalarNode('password_confirmation_label')
                            ->defaultValue('Repeat new password')
                        ->end()
                        ->scalarNode('password_mismatch_message')
                            ->defaultValue('The passwords do not match.')
                        ->end()
                        ->scalarNode('invalid_token_message')
                            ->defaultValue('This password reset link is invalid or has expired.')
                        ->end()
                        ->scalarNode('reset_success_message')
                            ->defaultValue('Your password has been reset.')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('login_route')
                    ->defaultValue('app_auth_login')
                ->end()
            ->end();

    }

    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $services = $configurator->services();
        $services->set(UserToolsViewConfig::class)
            ->args([
                $config['nav_button'],
                $config['details'],
                $config['items'],
                $config['logout'],
            ]);

        $services->set(UserToolsExtension::class)
            ->args([
                service(UserToolsViewConfig::class),
            ])
            ->tag('twig.extension');

        $repositoryClass = $config['repository_class'];
        if ($repositoryClass === UserRepository::class) {
            $services->set(UserRepository::class)
                ->args([service('doctrine'), $config['entity_class']]);
        }
        $services->alias(UserRepositoryInterface::class, $repositoryClass);

        if (!$container->hasAlias(PasswordForgotEmailInterface::class) && !$container->hasDefinition(PasswordForgotEmailInterface::class)) {
            $services->set(NullPasswordForgotEmail::class);
            $services->alias(PasswordForgotEmailInterface::class, NullPasswordForgotEmail::class);
        }

        $controllerClass = $config['controller_class'];
        $services
            ->set($controllerClass)
            ->args([
                '$detailsPage' => $config['details_page'],
                '$forgotPasswordPage' => $config['forgot_password_page'],
                '$loginRoute' => $config['login_route'],
            ])
            ->autowire()
            ->autoconfigure()
            ->tag('controller.service_arguments');

        if ($controllerClass !== UserController::class) {
            $services->alias(UserController::class, $controllerClass);
        }

        $services
            ->set(ForgotPasswordType::class)
            ->args([
                '$forgotPasswordPage' => $config['forgot_password_page'],
            ])
            ->autowire()
            ->autoconfigure()
            ->tag('form.type');

        $services
            ->set(ResetPasswordType::class)
            ->args([
                '$forgotPasswordPage' => $config['forgot_password_page'],
            ])
            ->autowire()
            ->autoconfigure()
            ->tag('form.type');
    }
}
