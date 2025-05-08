<?php

namespace App\Form;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\Forum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;

class PostEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Survey' => 'survey',
                    'Announcement' => 'announcement',
                ],
                'required' => true,
            ])
            ->add('id_user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'label' => 'Author',
                'disabled' => true, // Author cannot be changed
            ])
            ->add('forum_id', EntityType::class, [
                'class' => Forum::class,
                'choice_label' => 'name',
                'label' => 'Forum',
                'disabled' => true, // Forum cannot be changed
            ])
            ->add('chemin_fichier', FileType::class, [
                'required' => false,
                'label' => 'Image (PNG, JPG only)',
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid JPG or PNG image only',
                    ])
                ],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active' => 'active',
                    'Archived' => 'archived'
                ],
                'required' => true
            ]);

        // Add type-specific fields
        $builder
            ->add('survey_question', TextareaType::class, [
                'required' => false,
                'label' => 'Survey Question'
            ])
            ->add('survey_tags', TextType::class, [
                'required' => false,
                'label' => 'Survey Tags (comma separated)'
            ])
            ->add('announcement_title', TextType::class, [
                'required' => false,
                'label' => 'Announcement Title'
            ])
            ->add('announcement_content', TextareaType::class, [
                'required' => false,
                'label' => 'Announcement Content'
            ])
            ->add('announcement_tags', TextType::class, [
                'required' => false,
                'label' => 'Announcement Tags (comma separated)'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
