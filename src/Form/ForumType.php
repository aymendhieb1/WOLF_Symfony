<?php

namespace App\Form;

use App\Entity\Forum;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ForumType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('createdBy', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'mail',
            ])
            ->add('description', TextareaType::class)
            ->add('date_creation', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'datetimepicker']
            ])
            ->add('is_private', CheckboxType::class, [
                'required' => false,
                'label' => 'Is Private?'
            ])
            ->add('list_members', TextareaType::class, [
                'required' => true,
                'label' => 'Members List',
                'attr' => ['rows' => 5]
            ])
            ->add('post_count', IntegerType::class, [
                'label' => 'Post Count'
            ])
            ->add('nbr_members', IntegerType::class, [
                'label' => 'Number of Members'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Forum::class,
        ]);
    }
}