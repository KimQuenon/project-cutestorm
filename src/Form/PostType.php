<?php

namespace App\Form;

use App\Entity\Post;
use App\Form\PostImageType;
use App\Form\ApplicationType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class PostType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, $this->getConfiguration("Title of the frame:", 'Exemple : Giulia'))
            ->add('description', TextareaType::class, $this->getConfiguration("Description:", 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.'))
            ->add('commentDisabled', CheckboxType::class, [
                'label' => 'Disable comments',
                'required' => false,
            ]);
        ;

        if (!$options['is_edit']) {
            $builder->add('postImages', CollectionType::class, [
                'entry_type' => PostImageType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            'is_edit' => false,
        ]);
    }
}