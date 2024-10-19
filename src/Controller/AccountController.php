<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Avatar;
use App\Entity\Banner;
use App\Form\EditType;
use App\Form\AvatarType;
use App\Form\BannerType;
use App\Form\DeleteType;
use App\Entity\PasswordEdit;
use App\Form\PasswordEditType;
use App\Form\RegistrationType;
use Symfony\Component\Mime\Email;
use App\Repository\UserRepository;
use App\Repository\OrderRepository;
use App\Repository\ReportRepository;
use App\Repository\CommentRepository;
use Symfony\Component\Form\FormError;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ConversationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;

class AccountController extends AbstractController
{
    /**
     * Login
     *
     * @param AuthenticationUtils $utils
     * @return Response
     */
    #[Route('/login', name: 'account_login')]
    public function index(AuthenticationUtils $utils): Response
    {
        $error = $utils->getLastAuthenticationError();
        $username = $utils->getLastUsername();

        $loginError = null;

        if($error instanceof TooManyLoginAttemptsAuthenticationException)
        {
            $loginError= "Too many attempts, try later...";

        }

        return $this->render('account/index.html.twig', [
            'hasError' => $error !== null,
            'username'=> $username,
            'loginError'=> $loginError
        ]);
    }

    /**
     * Logout
     *
     * @return Void
     */
    #[Route('/logout', name: 'account_logout')]
    public function logout(): Void
    {
    }

    
    /**
     * Create an account + handle avatar & banner
     *
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @param UserPasswordHasherInterface $hasher
     * @return Response
     */
    #[Route("/register", name:"account_register")]
    public function register(Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $hasher, MailerInterface $mailer): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        // handle registration form
        if($form->isSubmitted() && $form->isValid())
        {
            $hash = $hasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hash);

            $user->setFirstname(ucwords($user->getFirstname()));
            $user->setLastname(ucwords($user->getLastname()));

            $avatarFile = $form->get('avatar')->getData();
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
                // if no avatar submitted => save the default one
                $user->setAvatar('default-avatar.webp');
            }

            $bannerFile = $form->get('banner')->getData();
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
                // if no avatar submitted => assign a random banner
                $defaultBanners = ['banner1.webp', 'banner2.webp', 'banner3.webp', 'banner4.webp', 'banner5.webp', 'banner6.webp', 'banner7.webp', 'banner8.webp', 'banner9.webp', 'banner10.webp'];
                $randomBanner = $defaultBanners[array_rand($defaultBanners)];
                $user->setBanner($randomBanner);
            }

            $manager->persist($user);
            $manager->flush();

            $email = (new Email())
                ->from('info@cutestorm.kimberley-quenon.be')
                ->to($user->getEmail())
                ->replyTo($user->getEmail())
                ->subject("Welcome to CuteStorm !")
                ->html($this->renderView('mail/register.html.twig', [
                    'user' => $user,
                ]));

            $mailer->send($email);


            return $this->redirectToRoute('account_login');
        }

        return $this->render("account/registration.html.twig",[
            'myForm'=>$form->createView()
        ]);
    }

    /**
     * Edit profile
     *
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    #[Route("/profile/edit", name:"profile_edit")]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, EntityManagerInterface $manager): Response
    {
        $user = $this->getUser(); //get connected user
        
        //edit form
        $form = $this->createForm(EditType::class, $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $user->setSlug('');

            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'success',
                'Data has been saved successfully'    
            );
            return $this->redirectToRoute('account_settings');
        }

        return $this->render("account/edit.html.twig",[
            'myForm'=>$form->createView(),
            'isPrivate' => $user->isPrivate(),
        ]);
    }

    /**
     * Display profile settings + edit options
     *
     * @param EntityManagerInterface $manager
     * @return Response
     */
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

    /**
     * Toggle between a public and a private account
     *
     * @param EntityManagerInterface $manager
     * @return Response
     */
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

    /**
     * edit password
     *
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @param UserPasswordHasherInterface $hasher
     * @return Response
     */
    #[Route("/profile/password-modify", name:"profile_password")]
    #[IsGranted('ROLE_USER')]
    public function modifyPassword(Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $hasher):Response
    {
        $passwordModify = new PasswordEdit();
        $user = $this->getUser();
        $form = $this->createForm(PasswordEditType::class, $passwordModify);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //compare given password & db password
            if (!password_verify($passwordModify->getOldPassword(), $user->getPassword())) {
                $form->get('oldPassword')->addError(new FormError("This doesn't seem to be your current password..."));
            }else{
                $newPassword = $passwordModify->getNewPassword();

                // new password = old password?
                if ($newPassword === $passwordModify->getOldPassword()) {
                    $form->get('newPassword')->addError(new FormError("The new password can't be the old one..."));
                } else {
                    $hash = $hasher->hashPassword($user, $newPassword);

                    $user->setPassword($hash);
                    $manager->persist($user);
                    $manager->flush();

                    $this->addFlash(
                        'success',
                        'Password edited successfully !'
                    );

                    return $this->redirectToRoute('account_settings');
                }
            }
        }

        return $this->render("account/password.html.twig",[
            'myForm' => $form->createView()
        ]);
    }

    /**
     * Add avatar
     *
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
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
            if(!empty($user->getAvatar() && $user->getAvatar() !== 'default-avatar.webp'))
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

    /**
     * replace current avatar by the default one
     *
     * @param EntityManagerInterface $manager
     * @return RedirectResponse
     */
    #[Route('/profile/avatar/delete', name: 'profile_avatar_delete')]
    #[IsGranted('ROLE_USER')]
    public function deleteAvatar(EntityManagerInterface $manager): RedirectResponse
    {
        $user = $this->getUser();

        if (!empty($user->getAvatar()) && $user->getAvatar() !== 'default-avatar.webp') {
            unlink($this->getParameter('uploads_directory').'/'.$user->getAvatar());
        }

        $user->setAvatar('default-avatar.webp');

        $manager->persist($user);
        $manager->flush();

        $this->addFlash('success', 'Avatar set to default.');

        return $this->redirectToRoute('profile_feed');
    }

    /**
     * add banner
     *
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
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

    /**
     * generator of default banner
     *
     * @param EntityManagerInterface $manager
     * @return RedirectResponse
     */
    #[Route('/profile/banner/change', name: 'profile_banner_change')]
    #[IsGranted('ROLE_USER')]
    public function changeBanner(EntityManagerInterface $manager): RedirectResponse
    {
        $user = $this->getUser();

        $defaultBanners = ['banner1.webp', 'banner2.webp', 'banner3.webp', 'banner4.webp', 'banner5.webp', 'banner6.webp', 'banner7.webp', 'banner8.webp', 'banner9.webp', 'banner10.webp'];

        // if already a default one => choose another one
        if (in_array($user->getBanner(), $defaultBanners)) {
            $availableBanners = array_diff($defaultBanners, [$user->getBanner()]);
            $randomBanner = $availableBanners[array_rand($availableBanners)];
        } else {
            // if not, delete the old file and replace it by a random one
            unlink($this->getParameter('uploads_directory').'/'.$user->getBanner());
            $randomBanner = $defaultBanners[array_rand($defaultBanners)];
        }

        $user->setBanner($randomBanner);

        $manager->persist($user);
        $manager->flush();

        $this->addFlash('success', 'Default banner randomly set.');

        return $this->redirectToRoute('profile_feed');
    }

    /**
     * delete banner and replace it by a random one
     *
     * @param EntityManagerInterface $manager
     * @return RedirectResponse
     */
    #[Route('/profile/banner/delete', name: 'profile_banner_delete')]
    #[IsGranted('ROLE_USER')]
    public function deleteBanner(EntityManagerInterface $manager): RedirectResponse
    {
        $user = $this->getUser();

        if (!empty($user->getBanner()) && !preg_match('/^banner\d+\.webp$/', $user->getBanner())) {
            $bannerPath = $this->getParameter('uploads_directory').'/'.$user->getBanner();
            if (file_exists($bannerPath)) {
                unlink($bannerPath); 
            }
        }

        $defaultBanners = ['banner1.webp', 'banner2.webp', 'banner3.webp', 'banner4.webp', 'banner5.webp', 'banner6.webp', 'banner7.webp', 'banner8.webp', 'banner9.webp', 'banner10.webp'];
        $randomBanner = $defaultBanners[array_rand($defaultBanners)];
        $user->setBanner($randomBanner);

        $manager->persist($user);
        $manager->flush();

        $this->addFlash('success', 'Banner set back to default.');

        return $this->redirectToRoute('profile_feed');
    }

    /**
     * Delete profile
     *
     * @param UserRepository $userRepo
     * @param ConversationRepository $convRepo
     * @param ReportRepository $reportRepo
     * @param CommentRepository $commentRepo
     * @param OrderRepository $orderRepo
     * @param Request $request
     * @param UserPasswordHasherInterface $hasher
     * @param EntityManagerInterface $manager
     * @param TokenStorageInterface $tokenStorage
     * @return Response
     */
    #[Route("/profile/delete", name: "profile_delete")]
    #[IsGranted('ROLE_USER')]
    public function deleteAccount(UserRepository $userRepo, ConversationRepository $convRepo, ReportRepository $reportRepo, CommentRepository $commentRepo, OrderRepository $orderRepo, Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $manager, TokenStorageInterface $tokenStorage, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(DeleteType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $submittedEmail = $data['email'];
            $submittedPassword = $data['password'];

            //email address in db?
            if ($user->getEmail() === $submittedEmail) {
                $isPasswordValid = $hasher->isPasswordValid($user, $submittedPassword);

                //password verify
                if ($isPasswordValid) {

                    $unpaidOrders = $orderRepo->findUnpaidOrders($user);
    
                    if ($unpaidOrders) {
                        $this->addFlash('warning', 'You still have unpaid invoices, please pay them before saying goodbye...');
                        return $this->redirectToRoute('orders_index');
                    } else {
                        $del = $userRepo->findOneBy(['email'=>'deleted@noreply.com']);
                        $convRepo->replaceUserInConversations($user, $del);
                        $commentRepo->replaceAuthorInComments($user, $del);
                        $orderRepo->replaceUserInOrders($user, $del);
        
                        foreach ($user->getLikeComments() as $likeComment) {
                            $manager->remove($likeComment);
                        }
        
                        $reportsByUser = $reportRepo->findBy(['reportedBy' => $user]);
                        foreach ($reportsByUser as $userReport) {
                            $manager->remove($userReport);
                        }
    
                        if ($user->getAvatar() && $user->getAvatar() !== 'default-avatar.webp') {
                            unlink($this->getParameter('uploads_directory').'/'.$user->getAvatar());
                        }
    
                        if ($user->getBanner() && !in_array($user->getBanner(), ['banner1.webp', 'banner2.webp', 'banner3.webp', 'banner4.webp', 'banner5.wepb', 'banner6.webp', 'banner7.webp', 'banner8.webp', 'banner9.webp', 'banner10.webp'])) {
                            unlink($this->getParameter('uploads_directory').'/'.$user->getBanner());
                        }
                    }

                    //set connexion token to null
                    $tokenStorage->setToken(null);

                    $email = (new Email())
                        ->from('info@cutestorm.kimberley-quenon.be')
                        ->to($user->getEmail())
                        ->replyTo($user->getEmail())
                        ->subject("Deleted Account")
                        ->html($this->renderView('mail/deletedAccount.html.twig', [
                            'user' => $user
                    ]));
                    $mailer->send($email);

                    //remove
                    $manager->remove($user);
                    $manager->flush();

                    $this->addFlash(
                        'success',
                        'Account deleted, see you soon maybe ?'
                    );

                    return $this->redirectToRoute('homepage');
                }
            }

            $this->addFlash(
                'danger',
                'Incorrect email address and/or password.'
            );
            return $this->redirectToRoute('profile_delete');
        }

        return $this->render('account/delete.html.twig', [
            'myForm' => $form->createView()
        ]);
    }

}