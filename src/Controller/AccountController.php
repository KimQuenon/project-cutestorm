<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Avatar;
use App\Entity\Banner;
use App\Form\AvatarType;
use App\Form\BannerType;
use App\Form\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

            $bannerFile = $form->get('banner')->getData(); // Adaptez cette ligne si nécessaire
            if ($bannerFile) {
                $originalFilename = pathinfo($bannerFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename."-".uniqid().'.'.$bannerFile->guessExtension();
                try {
                    $bannerFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                }catch(FileException $e){
                    return $e->getMessage();
                }
                $user->setBanner($newFilename);
            } else {
                $defaultBanners = [
                    'banner1.jpg',
                    'banner2.jpg',
                    'banner3.jpg',
                ];
                $randomBanner = $defaultBanners[array_rand($defaultBanners)];
                $user->setBanner($randomBanner);
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
            if(!empty($user->getAvatar() && $user->getAvatar() !== 'default-avatar.jpg'))
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

    #[Route('/profile/avatar/delete', name: 'profile_avatar_delete')]
    #[IsGranted('ROLE_USER')]
    public function deleteAvatar(EntityManagerInterface $manager): RedirectResponse
    {
        $user = $this->getUser();

        if (!empty($user->getAvatar()) && $user->getAvatar() !== 'default-avatar.jpg') {
            unlink($this->getParameter('uploads_directory').'/'.$user->getAvatar());
        }

        $user->setAvatar('default-avatar.jpg');

        $manager->persist($user);
        $manager->flush();

        $this->addFlash('success', 'Avatar set to default.');

        return $this->redirectToRoute('profile_feed');
    }


    #[Route("profile/banner", name:"profile_banner")]
    #[IsGranted('ROLE_USER')]
    public function banner(Request $request, EntityManagerInterface $manager):Response
    {
        $banner = new Banner();
        $user = $this->getUser();
        $form = $this->createForm(BannerType::class, $banner);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            //get rid of the old banner
            if(!empty($user->getBanner()))
            {
                unlink($this->getParameter('uploads_directory').'/'.$user->getBanner());
            }

            //handle img
            $file = $form['newBanner']->getData();
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
                $user->setBanner($newFilename);
            }
            $manager->persist($user);
            $manager->flush();


            $this->addFlash(
                'success',
                'Banner edited successfully !'    
            );

            return $this->redirectToRoute('profile_feed');
        }

        return $this->render("account/banner.html.twig",[
            'myForm'=>$form->createView()
        ]);
    }

    #[Route('/profile/banner/change', name: 'profile_banner_change')]
    #[IsGranted('ROLE_USER')]
    public function changeBanner(EntityManagerInterface $manager): RedirectResponse
    {
        $user = $this->getUser();

        $defaultBanners = ['banner1.jpg', 'banner2.jpg', 'banner3.jpg'];

        // Vérifier si la bannière actuelle est dans les bannières par défaut
        if (in_array($user->getBanner(), $defaultBanners)) {
            // Choisir une nouvelle bannière par défaut
            $availableBanners = array_diff($defaultBanners, [$user->getBanner()]);
            $randomBanner = $availableBanners[array_rand($availableBanners)];
        } else {
            // Si la bannière actuelle n'est pas par défaut, choisir une bannière aléatoire
            unlink($this->getParameter('uploads_directory').'/'.$user->getBanner());
            $randomBanner = $defaultBanners[array_rand($defaultBanners)];
        }

        // Réinitialiser la bannière de l'utilisateur
        $user->setBanner($randomBanner);

        $manager->persist($user);
        $manager->flush();

        $this->addFlash('success', 'Default banner randomly set.');

        return $this->redirectToRoute('profile_feed');
    }

    #[Route('/profile/banner/delete', name: 'profile_banner_delete')]
    #[IsGranted('ROLE_USER')]
    public function deleteBanner(EntityManagerInterface $manager): RedirectResponse
    {
        $user = $this->getUser();

        if (!empty($user->getBanner()) && $user->getBanner() !== 'banner1.jpg' && $user->getBanner() !== 'banner2.jpg' && $user->getBanner() !== 'banner3.jpg') {
            $bannerPath = $this->getParameter('uploads_directory').'/'.$user->getBanner();
            if (file_exists($bannerPath)) {
                unlink($bannerPath); 
            }
        }

        $defaultBanners = ['banner1.jpg', 'banner2.jpg', 'banner3.jpg'];
        $randomBanner = $defaultBanners[array_rand($defaultBanners)];
        $user->setBanner($randomBanner);

        $manager->persist($user);
        $manager->flush();

        $this->addFlash('success', 'Banner set back to default.');

        return $this->redirectToRoute('profile_feed');
    }

}