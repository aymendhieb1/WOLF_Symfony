<?php
namespace App\Form;

use App\Entity\Vol;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class VolType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('depart')
            ->add('destination')
            ->add('heureDepart', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'label' => 'Heure de départ'
            ])
            ->add('heureArrivee', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'label' => 'Heure d\'arrivée'
            ])
            ->add('classeChaise', ChoiceType::class, [
                'choices' => [
                    'Economique' => 'ECONOMY',
                    'Premium Économique' => 'PREMIUM_ECONOMY',
                    'Affaires' => 'BUSINESS',
                    'Première Classe' => 'FIRST_CLASS'
                ],
                'label' => 'Classe'
            ])
            ->add('compagnie', null, [
                'label' => 'Compagnie aérienne'
            ])
            ->add('prix', IntegerType::class, [
                'label' => 'Prix (€)'
            ])
            ->add('siegesDisponibles', IntegerType::class, [
                'label' => 'Sièges disponibles'
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['rows' => 5],
                'label' => 'Description du vol'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vol::class,
        ]);
    }
}