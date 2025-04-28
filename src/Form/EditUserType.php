<?php
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints\File;
class EditUserType extends AbstractType
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('mail', EmailType::class, [
                'label' => false,
                'constraints' => [
                    new NotBlank(['message' => 'L\'email ne peut pas être vide']),
                    new Callback([$this, 'validateEmailExists']),
                    new Regex([
                        'pattern' => '/^(?![.-])[A-Za-z0-9._-]+(?<![.-])@[A-Za-z0-9.-]+\.[A-Za-z]{2,6}$/',
                        'message' => 'L\'Email doit être sous forme user@example.com',
                    ]),
                ],
            ])
            ->add('nom', TextType::class, [
                'label' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre nom']),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas contenir plus de {{ limit }} caractères',
                    ]),
                    new Regex([
                        'pattern' => '/^[A-Za-zÀ-ÿ\'\- ]+$/',
                        'message' => 'Le nom ne peut contenir que des lettres, des espaces, des apostrophes et des tirets.',
                    ]),

                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => false,

                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre prénom']),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le prénom ne peut pas contenir plus de {{ limit }} caractères',
                    ]),
                    new Regex([
                        'pattern' => '/^[A-Za-zÀ-ÿ\'\- ]+$/',
                        'message' => 'Le prénom ne peut contenir que des lettres, des espaces, des apostrophes et des tirets.',
                    ]),

                ],
            ])
            ->add('num_tel', TextType::class, [
                'label' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre numéro de téléphone.']),
                    new Regex([
                        'pattern' => '/^\+\d{6,15}$/',
                        'message' => "Le numéro doit inclure l'indicatif, ex: +21612345678",
                    ]),
                ]
            ])




            ->add('photo_profil', HiddenType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (jpeg, png, gif)',
                        'maxSizeMessage' => 'Le fichier est trop volumineux (la taille maximale est de 5 Mo)',
                    ])
                ],
            ])

            ->add('mdp', PasswordType::class, [
                'label' => false,
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un mot de passe']),
                    new Length([
                        'min' => 8,
                        'max' => 20,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le mot de passe ne peut pas contenir plus de {{ limit }} caractères',
                    ]),
                    new Regex(['pattern' => '/[a-z]/', 'message' => 'Le mot de passe doit contenir au moins une lettre minuscule.']),
                    new Regex(['pattern' => '/[A-Z]/', 'message' => 'Le mot de passe doit contenir au moins une lettre majuscule.']),
                    new Regex(['pattern' => '/[0-9]/', 'message' => 'Le mot de passe doit contenir au moins un chiffre.']),
                ],
            ])
            ->add('newPassword', PasswordType::class, [
                'label' => false,
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un mot de passe']),
                    new Length([
                        'min' => 8,
                        'max' => 20,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le mot de passe ne peut pas contenir plus de {{ limit }} caractères',
                    ]),
                    new Regex(['pattern' => '/[a-z]/', 'message' => 'Le mot de passe doit contenir au moins une lettre minuscule.']),
                    new Regex(['pattern' => '/[A-Z]/', 'message' => 'Le mot de passe doit contenir au moins une lettre majuscule.']),
                    new Regex(['pattern' => '/[0-9]/', 'message' => 'Le mot de passe doit contenir au moins un chiffre.']),
                ],
            ]);

    }


    public function validateEmailExists($value, ExecutionContextInterface $context): void
    {
        $form = $context->getRoot();
        $user = $form->getData();
        
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['mail' => $value]);
        
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            $context->buildViolation('Cette adresse email est déjà utilisée')
                ->atPath('mail')
                ->addViolation();
        }
    }

    public function validateENumberExists($value, ExecutionContextInterface $context): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['num_tel' => $value]);

        if ($user) {
            $context->buildViolation('Numéro de téléphone déjà utilisé')
                ->atPath('num_tel')
                ->addViolation();
        }
    }



    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class
        ]);
    }
}
