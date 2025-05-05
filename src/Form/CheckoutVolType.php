<?php

namespace App\Form;

use App\Entity\CheckoutVol;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CheckoutVolType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('flightID', IntegerType::class, [
                'label' => 'ID du vol'
            ])
            ->add('aircraft', TextType::class, [
                'label' => 'Type d\'avion'
            ])
            ->add('flightCrew', IntegerType::class, [
                'label' => 'ID de l\'équipage'
            ])
            ->add('gate', TextType::class, [
                'label' => 'Porte'
            ])
            ->add('reservationDate', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'label' => 'Date et heure de réservation'
            ])
            ->add('totalPassengers', IntegerType::class, [
                'label' => 'Nombre total de passagers'
            ])
            ->add('reservationStatus', ChoiceType::class, [
                'choices' => [
                    'Confirmé' => 'Confirmee',
                    'En Attente' => 'En Attente',
                    'Annulé' => 'Annulee'
                ],
                'label' => 'Statut de la réservation'
            ])
            ->add('totalPrice', IntegerType::class, [
                'label' => 'Prix total (€)',
                'disabled' => true, // Disable to show as a calculated field
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CheckoutVol::class,
        ]);
    }
}
