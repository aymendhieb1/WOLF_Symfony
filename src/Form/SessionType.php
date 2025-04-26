<?php

namespace App\Form;

use App\Entity\Session;
use App\Entity\Activite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class SessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'format' => 'yyyy-MM-dd',
                'constraints' => [
                    new NotBlank(['message' => 'La date est obligatoire']),
                    new GreaterThanOrEqual([
                        'value' => new \DateTime('today'),
                        'message' => 'La date doit être supérieure ou égale à aujourd\'hui'
                    ])
                ],
                'label' => 'Date',
                'attr' => [
                    'class' => 'form-control',
                    'min' => (new \DateTime())->format('Y-m-d')
                ]
            ])
            ->add('heure', TimeType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime',
                'constraints' => [
                    new NotBlank(['message' => 'L\'heure est obligatoire'])
                ],
                'label' => 'Heure',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('capacite', IntegerType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1
                ],
                'label' => 'Capacité',
                'constraints' => [
                    new NotBlank(['message' => 'La capacité est obligatoire']),
                    new Positive(['message' => 'La capacité doit être un nombre positif']),
                    new Type([
                        'type' => 'integer',
                        'message' => 'La capacité doit être un nombre entier'
                    ])
                ]
            ])
            ->add('nbPlace', IntegerType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0
                ],
                'label' => 'Places disponibles',
                'constraints' => [
                    new NotBlank(['message' => 'Le nombre de places est obligatoire']),
                    new PositiveOrZero(['message' => 'Le nombre de places doit être un nombre positif ou zéro']),
                    new Type([
                        'type' => 'integer',
                        'message' => 'Le nombre de places doit être un nombre entier'
                    ]),
                    new Callback(function ($nbPlace, ExecutionContextInterface $context) {
                        $form = $context->getRoot();
                        $capacite = $form->get('capacite')->getData();
                        
                        if ($nbPlace !== null && $capacite !== null && $nbPlace > $capacite) {
                            $context->buildViolation('Le nombre de places disponibles ne peut pas être supérieur à la capacité')
                                ->addViolation();
                        }
                    })
                ]
            ])
            ->add('activite', EntityType::class, [
                'class' => Activite::class,
                'choice_label' => 'nom',
                'placeholder' => 'Choisir une activité',
                'constraints' => [
                    new NotBlank(['message' => 'L\'activité est obligatoire'])
                ],
                'label' => 'Activité',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Session::class,
        ]);
    }
} 