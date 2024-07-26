<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Like;
use App\Entity\Post;
use App\Entity\User;
use App\Entity\Comment;
use App\Entity\Following;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('en_EN');

        // Create an anonymous user
        $anon = new User();
        $hash = $this->passwordHasher->hashPassword($anon, 'password');

        $anon->setPseudo('Anonymous User')
            ->setFirstname('Anon')
            ->setLastname('User')
            ->setTimestamp($faker->dateTimeBetween('-1 year', '-1 month'))
            ->setAddress('XXXXXXXXXXXXXXXXXXXXXXXXXX')
            ->setPostalcode('00000')
            ->setCity('XXXXXX')
            ->setCountry('XXXXXXXXX')
            ->setEmail("anon@noreply.com")
            ->setPassword($hash)
            ->setBio('XXXXXXXXXXXXXXXXXXXXX')
            ->setAvatar('')
            ->setBanner('')
            ->setPrivate(true);

        $manager->persist($anon);

        $users = []; // Array to store users
        $userCount = 10; // Number of users to create

        // Create users
        for ($u = 1; $u <= $userCount; $u++) {
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
                ->setBio('<p>' . join('<p></p>', $faker->paragraphs(1)) . '</p>')
                ->setAvatar('https://picsum.photos/seed/picsum/500/500')
                ->setBanner('https://picsum.photos/seed/picsum/500/500')
                ->setPrivate($faker->boolean());

            $manager->persist($user);
            $users[] = $user; // Add user to the array
        }

        // Create posts
        $posts = [];
        for ($i = 1; $i <= 30; $i++) {
            $post = new Post();
            $post->setTitle($faker->sentence())
                ->setDescription('<p>' . join('</p><p>', $faker->paragraphs(2)) . '</p>')
                ->setTimestamp($faker->dateTimeBetween('-1 year', '-1 month'))
                ->setAuthor($users[array_rand($users)]); // Random author
            $manager->persist($post);
            $posts[] = $post;
        }

        // Create comments and replies
        foreach ($posts as $post) {
            $commentCount = rand(2, 15); // Each post gets between 2 to 15 comments

            for ($c = 0; $c < $commentCount; $c++) {
                $comment = new Comment();
                $comment->setContent($faker->paragraph())
                        ->setTimestamp($faker->dateTimeBetween('-1 year', 'now'))
                        ->setAuthor($users[array_rand($users)]) // Random user as author
                        ->setPost($post);
                $manager->persist($comment);

                // Randomly add replies to some comments
                $replyCount = rand(0, 3); // Each comment can have up to 3 replies
                for ($r = 0; $r < $replyCount; $r++) {
                    $reply = new Comment();
                    $reply->setContent($faker->paragraph())
                          ->setTimestamp($faker->dateTimeBetween('-1 year', 'now'))
                          ->setAuthor($users[array_rand($users)]) // Random user as author
                          ->setPost($post)
                          ->setParent($comment); // Set the parent comment
                    $manager->persist($reply);
                }
            }
        }

        // Create likes
        foreach ($posts as $post) {
            $postAuthor = $post->getAuthor();
            $potentialLikers = array_filter($users, fn($user) => $user !== $postAuthor);
            $potentialLikers = array_values($potentialLikers);

            for ($l = 0; $l < rand(1, 10); $l++) { // Each post gets between 1 to 10 likes
                $like = new Like();
                $like->setUser($potentialLikers[array_rand($potentialLikers)])
                     ->setPost($post);
                $manager->persist($like);
            }
        }

        // Create followings (follower and followed relationships)
        for ($u = 0; $u < $userCount; $u++) {
            $follower = $users[$u];
            $followingCount = rand(1, 5); // Number of followings for this user

            // Ensure the user does not follow themselves
            $followedUsers = array_filter($users, fn($user) => $user !== $follower);

            // Shuffle and slice to ensure randomness
            shuffle($followedUsers);
            $followedUsers = array_slice($followedUsers, 0, $followingCount);

            foreach ($followedUsers as $followed) {
                $following = new Following();
                $following->setFollowerUser($follower)
                          ->setFollowedUser($followed);
                $manager->persist($following);
            }
        }

        $manager->flush();
    }
}

