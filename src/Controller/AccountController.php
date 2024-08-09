<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Avatar;
use App\Form\AvatarType;
use App\Form\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;

class AccountController extends AbstractController
{
    #[Route('/login', name: 'account_login')]
    public function index(AuthenticationUtils $utils): Response
    {
        $error = $utils->getLastAuthenticationError();
        $username = $utils->getLastUsername();

        $loginError = null;

        if($error instanceof TooManyLoginAttemptsAuthenticationException)
        {
            $loginError= "Trop de tentatives de connexion, réessayez plus tard...";

        }

        return $this->render('account/index.html.twig', [
            'hasError' => $error !== null,
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

            $avatarFile = $form->get('avatar')->getData(); // Adaptez cette ligne si nécessaire
            if ($avatarFile) {
                $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename."-".uniqid().'.'.$avatarFile->guessExtension();
                try {
                    $avatarFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                }catch(FileException $e){
                    return $e->getMessage();
                }
                $user->setAvatar($newFilename);
            } else {
                // Assigner l'avatar par défaut
                $user->setAvatar('default-avatar.jpg');
            }

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

    #[Route("profile/avatar", name:"profile_avatar")]
    #[IsGranted('ROLE_USER')]
    public function avatar(Request $request, EntityManagerInterface $manager):Response
    {
        $avatar = new Avatar();
        $user = $this->getUser();
        $form = $this->createForm(AvatarType::class, $avatar);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            //get rid of the old avatar
            if(!empty($user->getAvatar()))
            {
                unlink($this->getParameter('uploads_directory').'/'.$user->getAvatar());
            }

            //handle img
            $file = $form['newAvatar']->getData();
            if(!empty($file))
            {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename."-".uniqid().'.'.$file->guessExtension();
                try{
                    $file->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                }catch(FileException $e){
                    return $e->getMessage();
                }
                $user->setAvatar($newFilename);
            }
            $manager->persist($user);
            $manager->flush();


            $this->addFlash(
                'success',
                'Avatar edited successfully !'    
            );

            return $this->redirectToRoute('profile_feed');
        }

        return $this->render("account/avatar.html.twig",[
            'myForm'=>$form->createView()
        ]);
    }

}