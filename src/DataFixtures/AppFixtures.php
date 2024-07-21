<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Like;
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
        $faker = Factory::create('en_EN');

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
                ->setPostalcode(intval($faker->postcode()))
                ->setCity($faker->city())
                ->setCountry($faker->country())
                ->setEmail($faker->email())
                ->setPassword($hash)
                ->setBio('<p>'.join('<p></p>',$faker->paragraphs(1)).'</p>')
                ->setAvatar('https://picsum.photos/seed/picsum/500/500')
                ->setBanner('https://picsum.photos/seed/picsum/500/500');

                $manager->persist($user);

                $users[] = $user; //ajouter un user au tableau pour les annonces
        }

        $posts = [];

        for($i=1; $i<=30; $i++)
        {

            $post = new Post();

            $post->setTitle($faker->sentence())
                ->setDescription('<p>'.join('</p><p>', $faker->paragraphs(2)).'</p>')
                ->setTimestamp($faker->dateTimeBetween('-1 year', '-1 month'))
                ->setAuthor($users[rand(0, count($users)-1)]);
            $manager->persist($post);

            $posts[] = $post;
        }

        foreach ($posts as $post) {
            // Filter out the author from the list of potential likers
            $postAuthor = $post->getAuthor();
            $potentialLikers = array_filter($users, function($user) use ($postAuthor) {
                return $user !== $postAuthor;
            });

            $potentialLikers = array_values($potentialLikers); // Reindex the array

            for ($l = 0; $l < rand(1, 10); $l++) { // Each post gets between 1 to 10 likes
                $like = new Like();
                $like->setUser($potentialLikers[rand(0, count($potentialLikers) - 1)])
                     ->setPost($post);

                $manager->persist($like);
            }
        }

        $manager->flush();
    }
}
