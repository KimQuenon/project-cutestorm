<?php

namespace App\Form;

use App\Form\ApplicationType;
use App\Entity\ProductVariant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class AddToCartType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('productVariant', EntityType::class, [
            'class' => ProductVariant::class,
            'choices' => $options['product_variants'],
            'choice_label' => function (ProductVariant $variant) {
                return $variant->getSize();
            },
            'label_html' => false,
            'expanded' => true,
            'multiple' => false,
            'choice_attr' => function (ProductVariant $variant) {
                return [
                    'data-stock' => $variant->getStock(),
                    'class' => 'product-variant-option'
                ];
            },
        ])
        ->add('quantity', IntegerType::class, [
            'data' => 1,
            'attr' => [
                'min' => 1,
                'max' => 99,
            ],
        ]);
    
    
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'product_variants' => [], // Pass available variants to the form
        ]);
    }
}

