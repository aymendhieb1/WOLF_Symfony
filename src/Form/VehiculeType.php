<?php
namespace App\Form;

use App\Entity\Vehicule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class VehiculeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('matricule', null, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le matricule ne peut pas être vide.']),
                    new Assert\Length([
                        'min' => 3,
                        'minMessage' => 'Le matricule doit avoir au moins 3 caractères.',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[A-Za-z0-9\-]+$/',
                        'message' => 'Le matricule ne peut contenir que des lettres, chiffres ou tirets.'
                    ]),
                ],
            ])
            ->add('status', null, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le statut ne peut pas être vide.']),
                    new Assert\Length([
                        'min' => 3,
                        'minMessage' => 'Le statut doit avoir au moins 3 caractères.',
                    ]),
                ],
            ])
            ->add('nbPlace', null, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nombre de places ne peut pas être vide.']),
                    new Assert\Positive(['message' => 'Le nombre de places doit être positif.']),
                ],
            ])
            ->add('cylinder', null, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le cylindre ne peut pas être vide.']),
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Le cylindre doit être ≥ 0.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicule::class,
            'csrf_protection' => false, // Disabled for JSON API
        ]);
    }
}