<?php

namespace App\Form;

use App\Entity\Product;
use App\Form\ApplicationType;
use App\Entity\ProductVariant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class ProductVariantType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('size', ChoiceType::class, [
            'choices'=>[
                '36'=>'36',
                '37'=>'37',
                '38'=>'38',
                '39'=>'39',
                '40'=>'40',
                '41'=>'41',
                '42'=>'42',
                '43'=>'43',
                '44'=>'44',
                '45'=>'45',
                '46'=>'46',
                '47'=>'47',
                '48'=>'48',
                '49'=>'49',
                '50'=>'50',
                '00'=>'00',
            ]])
            ->add('stock', IntegerType::class, $this->getConfiguration("Stock:", '...'))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductVariant::class,
        ]);
    }
}
