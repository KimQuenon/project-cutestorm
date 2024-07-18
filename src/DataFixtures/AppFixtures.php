<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    private $passwordHasher;

    /**
     * Hash password
     *
     * @param UserPasswordHasherInterface $passwordHasher
     */
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }
    
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $users = []; //array to stock users

        for($u = 1; $u <= 10; $u++)
        {
            $user = new User();
            $hash = $this->passwordHasher->hashPassword($user, 'password');

            $user->setPseudo($faker->name())
                ->setFirstname($faker->firstName())
                ->setLastname($faker->lastName())
                ->setTimestamp($faker->dateTimeBetween('-1 year', '-1 month'))
                ->setAddress($faker->streetAddress())
                ->setPostalcode($faker->postcode())
                ->setCity($faker->city())
                ->setCountry($faker->country())
                ->setEmail($faker->email())
                ->setPassword($hash)
                ->setBio('<p>'.join('<p></p>',$faker->paragraphs(1)).'</p>');

                $manager->persist($user);

                $users[] = $user; //ajouter un user au tableau pour les annonces
        }

        for($i=1; $i<=30; $i++)
        {
            $post = new Post();

            $post->setTitle($faker->sentence())
                ->setDescription('<p>'.join('</p><p>', $faker->paragraphs(5)).'</p>')
                ->setTimestamp($faker->dateTimeBetween('-1 year', '-1 month'));
            $manager->persist($post);
        }

        $manager->flush();
    }
}
