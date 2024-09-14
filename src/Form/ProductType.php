<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\ProductColor;
use App\Form\ApplicationType;
use App\Form\ProductImageType;
use App\Entity\ProductCategory;
use App\Form\ProductVariantType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class ProductType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reference', TextType::class, $this->getConfiguration("REF SKU:", '0755-07550755'))
            ->add('name', TextType::class, $this->getConfiguration("Name:", 'I.E. : White jacket...'))
            ->add('description', TextareaType::class, $this->getConfiguration("Description:", 'I.E. : Long white jacket...'))
            ->add('price', MoneyType::class, $this->getConfiguration("Price:", '...'))
            ->add('color', EntityType::class, [
                'label' => "Color:",
                'class' => ProductColor::class,
                'choice_label' => 'name',
                'required' => true,
            ])
            ->add('productVariants', CollectionType::class, [
                'entry_type' => ProductVariantType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
            ])
            ->add('productCategories', EntityType::class, [
                'class' => ProductCategory::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'required' => true,
            ])
        ;

        if (!$options['is_edit']) {
            $builder->add('productImages', CollectionType::class, [
                'entry_type' => ProductImageType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'is_edit' => false,
        ]);
    }
}
