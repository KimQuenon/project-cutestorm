<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;

class AccountController extends AbstractController
{
    #[Route('/login', name: 'account_login')]
    public function index(AuthenticationUtils $utils): Response
    {
        $error = $utils->getLastAuthenticationError(); //récupérer la dernière erreur engendrée
        $username = $utils->getLastUsername(); //récupérer le dernier username à s'être connecté

        $loginError = null; //init la var pour toomanyattempts

        if($error instanceof TooManyLoginAttemptsAuthenticationException)
        {
            //l'erreur est due à la limitation de tentative de connexion
            $loginError= "Trop de tentatives de connexion, réessayez plus tard...";

        }

        return $this->render('account/index.html.twig', [
            'hasError' => $error !== null, //pas nul => afficher erreur
            'username'=> $username,
            'loginError'=> $loginError
        ]);
    }

    #[Route('/logout', name: 'account_logout')]
    public function logout(): Void
    {
    }

    
    #[Route("/register", name:"account_register")]
    public function register(Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $hasher): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);


        if($form->isSubmitted() && $form->isValid())
        {
            $hash = $hasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hash);

            $user->setFirstname(ucwords($user->getFirstname()));
            $user->setLastname(ucwords($user->getLastname()));

            $manager->persist($user);
            $manager->flush();


            return $this->redirectToRoute('account_login');
        }

        return $this->render("account/registration.html.twig",[
            'myForm'=>$form->createView()
        ]);
    }

    #[Route('/profile/settings', name: 'account_settings')]
    #[IsGranted('ROLE_USER')]
    public function settings(EntityManagerInterface $manager): Response
    {
        $user = $this->getUser();

        return $this->render('account/settings.html.twig', [
            'user' => $user,
            'isPrivate' => $user->isPrivate(),
        ]);
    }

    #[Route('/toggle-private', name: 'toggle_private')]
    #[IsGranted('ROLE_USER')]
    public function togglePrivate(EntityManagerInterface $manager): Response
    {
        $user = $this->getUser();

        $user->setPrivate(!$user->isPrivate());

        $manager->persist($user);
        $manager->flush();

        $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

        return $this->redirectToRoute('account_settings');
    }

}