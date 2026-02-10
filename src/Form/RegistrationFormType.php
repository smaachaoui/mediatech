<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Sequentially;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champs mappés -> on laisse les contraintes dans l'Entity User.php
            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo',
                'attr' => [
                    'autocomplete' => 'nickname',
                    'placeholder' => 'Ex : MediaFan42',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'autocomplete' => 'email',
                    'placeholder' => 'exemple@domaine.fr',
                ],
            ])

            // Non mappé -> contraintes ici (OK)
            ->add('agreeTerms', CheckboxType::class, [
                'label' => "J'accepte les conditions d'utilisation",
                'mapped' => false,
                'constraints' => [
                    new IsTrue(['message' => "Vous devez accepter les conditions d'utilisation."]),
                ],
            ])

            // Non mappé -> contraintes ici (OK)
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Minimum 8 caractères',
                ],
                'constraints' => [
                    // Sequentially => stop au 1er message (évite “3 erreurs en même temps”)
                    new Sequentially([
                        'constraints' => [
                            new NotBlank(['message' => 'Le mot de passe est obligatoire.']),
                            new Length([
                                'min' => 8,
                                'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                                'max' => 4096,
                            ]),
                            new Regex([
                                'pattern' => '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/',
                                'message' => 'Le mot de passe doit contenir une majuscule, une minuscule, un chiffre et un caractère spécial.',
                            ]),
                        ],
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
