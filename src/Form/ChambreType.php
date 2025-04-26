<?php

namespace App\Form;

use App\Entity\Chambre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Hotel;

class ChambreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type de chambre',
                'choices' => [
                    'Simple' => 'Simple',
                    'Double' => 'Double',
                    'Suite' => 'Suite',
                    'Deluxe' => 'Deluxe',
                    'Executive Room' => 'Executive Room'
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix par nuit',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'step' => '0.01'
                ]
            ])
            ->add('disponibilite', ChoiceType::class, [
                'label' => 'Disponibilité',
                'choices' => [
                    'Disponible' => 'Disponible',
                    'Occupée' => 'Occupée',
                    'En maintenance' => 'En maintenance'
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Description de la chambre'
                ]
            ])
            ->add('hotel', EntityType::class, [
                'class' => Hotel::class,
                'choice_label' => 'nom',
                'label' => 'Hôtel',
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Chambre::class,
        ]);
    }
} 