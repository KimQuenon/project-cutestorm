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
                    'Spam' => 'spam',
                    'Harassment' => 'harassment',
                    'Hate Speech' => 'hate_speech',
                    'Violence' => 'violence',
                    'Nudity or Sexual Content' => 'nudity_or_sexual_content',
                    'Misinformation' => 'misinformation',
                    'Self-Harm' => 'self_harm',
                    'Impersonation' => 'impersonation',
                    'Scam or Fraud' => 'scam_or_fraud',
                    'Copyright Violation' => 'copyright_violation',
                    'Threats' => 'threats',
                    'Inappropriate Language' => 'inappropriate_language',
                    'Terrorism' => 'terrorism',
                    'Privacy Violation' => 'privacy_violation',
                    'Other' => 'other',
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
