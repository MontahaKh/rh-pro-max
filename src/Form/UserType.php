<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\UserRole;
use App\Enum\UserStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'required' => true,
            ])

            // ⚠️ password: en edit on le laisse vide => il ne faut pas écraser l’ancien password
            ->add('password', PasswordType::class, [
                'required' => $options['is_create'], // true en create, false en edit
                'mapped' => false,                   // très important: on ne mappe PAS direct sur l'entity
                'empty_data' => '',
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => $options['is_create'] ? 'Enter password' : 'Leave blank to keep current password'
                ],
            ])

            ->add('status', EnumType::class, [
                'class' => UserStatus::class,
                'placeholder' => 'Choose status',
                'required' => false,
                'choice_label' => fn (UserStatus $s) => $s->value,
                'choice_value' => fn (?UserStatus $s) => $s?->value,
            ])

            // ✅ FIX ENUM ROLE
            ->add('role', EnumType::class, [
                'class' => UserRole::class,
                'placeholder' => 'Choose role',
                'required' => false,
                'choice_label' => fn (UserRole $r) => $r->value,
                'choice_value' => fn (?UserRole $r) => $r?->value,
            ])

            ->add('matricule', TextType::class, [
                'required' => false,
            ])
            ->add('firstName', TextType::class, [
                'required' => false,
            ])
            ->add('lastName', TextType::class, [
                'required' => false,
            ])

            ->add('birthday', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('hireDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
            ])

            ->add('department', TextType::class, [
                'required' => false,
            ])
            ->add('jobTitle', TextType::class, [
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,

            // permet de différencier Create / Edit
            'is_create' => true,
        ]);
    }
}
