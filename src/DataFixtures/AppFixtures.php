<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Post;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

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
