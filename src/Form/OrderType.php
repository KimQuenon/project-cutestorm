<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Order;
use App\Entity\Delivery;
use App\Form\ApplicationType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class OrderType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('delivery', ChoiceType::class, [
                'choices' => $options['deliveries'],
                'choice_label' => function(Delivery $delivery) {
                    return sprintf(

                        '<p class="text-tertiary-dark"><strong>%s</strong></p><p>%s</p><p>%s $</p>',

                        $delivery->getName(),

                        $delivery->getDeliveryTime(),

                        $delivery->getPrice()

                    );
                },
                'choice_value' => 'id',
                'multiple' => false,
                'expanded' => true,
                'choice_attr' => function (Delivery $delivery) {
                    return [
                        'data-price' => $delivery->getPrice(),
                    ];
                },
                'label_html' => true, // Enable HTML rendering for the label
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
            'deliveries' => [],
        ]);
    }
}
