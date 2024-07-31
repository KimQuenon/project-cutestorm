<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Report;
use App\Form\ApplicationType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ReportType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reason', ChoiceType::class, [
                'choices'=>[
                    //options = label, value = database
                    'Oil on canvas'=>'Oil on canvas',
                    'Acrylic'=>'Acrylic',
                    'Watercolor'=>'Watercolor',
                    'Sketch' => 'Sketch',
                    'Gouache'=>'Gouache',
                    'Encaustic'=>'Encaustic',
                    'Tempera'=>'Tempera',
                    'Pastel'=>'Pastel',
                    'Spray'=>'Spray',
                    'Ink'=>'Ink',
                    'Other'=>'Other'
                ]])
            ->add('details', TextareaType::class, $this->getConfiguration("Additional details :", 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',[
                'required' => false,
            ]))

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Report::class,
        ]);
    }
}
