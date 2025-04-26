<?php

namespace App\Form;

use App\Entity\Contrat;
use App\Entity\Vehicule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ContratType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateD', TextType::class, [
                'label' => 'Date de début',
                'required' => true,
                'attr' => [
                    'class' => 'datepicker',
                    'placeholder' => 'YYYY-MM-DD'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'La date de début est requise',
                    ]),
                    new Regex([
                        'pattern' => '/^\d{4}-\d{2}-\d{2}$/',
                        'message' => 'Format de date invalide. Utilisez le format YYYY-MM-DD'
                    ])
                ],
            ])
            ->add('dateF', TextType::class, [
                'label' => 'Date de fin',
                'required' => true,
                'attr' => [
                    'class' => 'datepicker',
                    'placeholder' => 'YYYY-MM-DD'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'La date de fin est requise',
                    ]),
                    new Regex([
                        'pattern' => '/^\d{4}-\d{2}-\d{2}$/',
                        'message' => 'Format de date invalide. Utilisez le format YYYY-MM-DD'
                    ])
                ],
            ])
            ->add('cinlocateur', TextType::class, [
                'label' => 'CIN Locataire',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Le CIN locataire est requis.']),
                    new Length(['min' => 8, 'max' => 8, 'exactMessage' => 'Le CIN doit contenir exactement 8 chiffres.']),
                    new Regex(['pattern' => '/^\d{8}$/', 'message' => 'Le CIN doit être composé de 8 chiffres.']),
                ],
            ])
            ->add('id_vehicule', EntityType::class, [
                'label' => 'Véhicule',
                'class' => Vehicule::class,
                'choice_label' => 'matricule',
                'required' => true,
                'placeholder' => 'Sélectionnez un véhicule',
                'constraints' => [
                    new NotBlank(['message' => 'Le véhicule est requis.']),
                ],
            ])
            ->add('photo_permit', FileType::class, [
                'label' => 'Photo Permis',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, GIF).',
                        'maxSizeMessage' => 'Le fichier est trop volumineux (max 2M).',
                    ]),
                ],
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            
            if (isset($data['dateD']) && isset($data['dateF'])) {
                try {
                    $dateD = new \DateTime($data['dateD']);
                    $dateF = new \DateTime($data['dateF']);
                    
                    if ($dateF < $dateD) {
                        $form->get('dateF')->addError(new FormError('La date de fin doit être postérieure à la date de début'));
                    }
                } catch (\Exception $e) {
                    // Date parsing error will be caught by the Regex constraint
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contrat::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ]);
    }
}