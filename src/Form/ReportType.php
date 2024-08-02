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
                    'Spam' => 'Spam',
                    'Harassment' => 'Harassment',
                    'Hate Speech' => 'Hate Speech',
                    'Violence' => 'violence',
                    'Nudity or Sexual Content' => 'Nudity or Sexual Content',
                    'Misinformation' => 'Misinformation',
                    'Self-Harm' => 'Self-Harm',
                    'Impersonation' => 'Impersonation',
                    'Scam or Fraud' => 'Scam or Fraud',
                    'Copyright Violation' => 'Copyright Violation',
                    'Threats' => 'Threats',
                    'Inappropriate Language' => 'Inappropriate Language',
                    'Terrorism' => 'Terrorism',
                    'Privacy Violation' => 'Privacy Violation',
                    'Other' => 'Other',
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
