<?php

namespace App\Form;

use App\Entity\ResVol;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResVolType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('idUser', HiddenType::class)
            ->add('flightId', HiddenType::class)
            ->add('departure', TextType::class, [
                'label' => 'Départ',
                'attr' => ['class' => 'form-control']
            ])
            ->add('destination', TextType::class, [
                'label' => 'Destination',
                'attr' => ['class' => 'form-control']
            ])
            ->add('departureTime', DateTimeType::class, [
                'label' => 'Heure de Départ',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'style' => 'background-color: #f8f9fa; border: 1px solid #ced4da;'
                ]
            ])
            ->add('arrivalTime', DateTimeType::class, [
                'label' => 'Heure d\'Arrivée',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'style' => 'background-color: #f8f9fa; border: 1px solid #ced4da;'
                ]
            ])
            ->add('classeChaise', TextType::class, [
                'label' => 'Classe',
                'attr' => ['class' => 'form-control']
            ])
            ->add('flightPrice', IntegerType::class, [
                'label' => 'Prix',
                'attr' => ['class' => 'form-control']
            ])
            ->add('airline', TextType::class, [
                'label' => 'Compagnie',
                'attr' => ['class' => 'form-control']
            ])
            ->add('volReservationDate', DateTimeType::class, [
                'disabled' => true,
                'label' => 'Date de Réservation',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'style' => 'background-color: #f8f9fa; border: 1px solid #ced4da;'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ResVol::class,
        ]);
    }
} 