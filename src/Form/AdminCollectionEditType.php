<?php

namespace App\Form;

use App\Entity\Collection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class AdminCollectionEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(message: 'Le nom est obligatoire.'),
                    new Length(max: 120, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'),
                ],
                'attr' => [
                    'maxlength' => 120,
                ],
            ])
            ->add('genre', TextType::class, [
                'label' => 'Genre',
                'required' => false,
                'constraints' => [
                    new Length(max: 80, maxMessage: 'Le genre ne peut pas dépasser {{ limit }} caractères.'),
                ],
                'attr' => [
                    'maxlength' => 80,
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'constraints' => [
                    new Length(max: 1000, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'),
                ],
                'attr' => [
                    'rows' => 5,
                    'maxlength' => 1000,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Collection::class,
        ]);
    }
}
