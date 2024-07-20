<?php

namespace App\Form;

use App\Entity\User;
use App\Form\ApplicationType;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class RegistrationType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $countries = Countries::getNames();
        
        $builder
            ->add('email', EmailType::class, $this->getConfiguration("Mail", "john.doe@gmail.com"))
            ->add('password', PasswordType::class, $this->getConfiguration("Password", "••••••••••"))
            ->add('passwordConfirm', PasswordType::class, $this->getConfiguration('Confirm your password', "••••••••••"))
            ->add('pseudo', TextType::class, $this->getConfiguration("Pseudo", "Be unique, be creative"))
            ->add('firstname', TextType::class, $this->getConfiguration("First name", "John"))
            ->add('lastname', TextType::class, $this->getConfiguration("Last name", "Doe"))
            ->add('address', TextType::class, $this->getConfiguration("Address", "New Street 77"))
            ->add('postalcode', IntegerType::class, $this->getConfiguration("Post Code:", 'Exemple : 75000'))
            ->add('city', TextType::class, $this->getConfiguration("City", "London"))
            ->add('country', ChoiceType::class, [
                'label' => 'Country',
                'choices' => array_flip($countries), // Inverse les clés et les valeurs pour les choix
                'placeholder' => 'Choose your country',
            ])
            ->add('bio', TextareaType::class, $this->getConfiguration("Description:", 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.'))
            ->add('avatar', FileType::class,[
                'label'=>"Avatar (jpg, png, gif)",
                'required'=>false
            ])
            ->add('banner', FileType::class,[
                'label'=>"Avatar (jpg, png, gif)",
                'required'=>false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
