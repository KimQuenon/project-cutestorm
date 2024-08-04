<?php

namespace App\Form;

use App\Entity\Product;
use App\Form\ApplicationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
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
            ->add('colors', ChoiceType::class, [
                'choices' => [
                    'Red' => 'red',
                    'Green' => 'green',
                    'Blue' => 'blue',
                    'White' => 'white',
                    'Black' => 'black',
                ],
                'multiple' => true,
                'expanded' => true,
                'label' => 'Colors',
            ])
            ->add('sizes', ChoiceType::class, [
                'choices' => [
                    '36' => '36',
                    '37' => '37',
                    '38' => '38',
                    '39' => '39',
                ],
                'multiple' => true,
                'expanded' => true,
                'label' => 'Sizes',
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
