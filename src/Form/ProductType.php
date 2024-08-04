<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\ProductColor;
use App\Form\ApplicationType;
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
                'class' => ProductColor::class,
                'choice_label' => 'name', // Choix basé sur le nom de la couleur
                'required' => true, // Marque le champ comme obligatoire
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
