<?php

namespace NandoJans\UserTools\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ForgotPasswordType extends AbstractType
{
    public function __construct(
        private readonly array $forgotPasswordPage,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'attr' => [
                'autocomplete' => 'off',
            ],
            'label' => $this->forgotPasswordPage['email_label'],
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Email(),
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}