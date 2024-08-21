<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Like;
use App\Entity\Post;
use App\Entity\Team;
use App\Entity\User;
use App\Entity\Review;
use App\Entity\Comment;
use App\Entity\Contact;
use App\Entity\Message;
use App\Entity\Product;
use App\Entity\Delivery;
use App\Entity\Following;
use App\Entity\LikeComment;
use App\Entity\Conversation;
use App\Entity\ProductColor;
use App\Entity\ProductVariant;
use App\Entity\ProductCategory;
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

        $userDeleted = new User();
        $hash = $this->passwordHasher->hashPassword($userDeleted, 'password');

        $userDeleted->setPseudo('Deleted User')
            ->setFirstname('Deleted')
            ->setLastname('User')
            ->setTimestamp($faker->dateTimeBetween('-1 year', '-1 month'))
            ->setAddress('XXXXXXXXXXXXXXXXXXXXXXXXXX')
            ->setPostalcode('00000')
            ->setCity('XXXXXX')
            ->setCountry('XXXXXXXXX')
            ->setEmail("deleted@noreply.com")
            ->setPassword($hash)
            ->setBio('XXXXXXXXXXXXXXXXXXXXX')
            ->setAvatar('')
            ->setBanner('')
            ->setPrivate(true);
    
        $manager->persist($userDeleted);

        $admin = new User();

        $admin->setPseudo('Admin')
                ->setRoles(['ROLE_ADMIN', 'ROLE_MODERATOR'])
                ->setFirstname('Kim')
                ->setLastname('Quenon')
                ->setTimestamp($faker->dateTimeBetween('-1 year', '-1 month'))
                ->setAddress($faker->streetAddress())
                ->setPostalcode(intval($faker->postcode()))
                ->setCity($faker->city())
                ->setCountry($faker->country())
                ->setEmail('admin@cutestorm.be')
                ->setPassword($this->passwordHasher->hashPassword($admin, 'admin'))
                ->setBio('<p>' . join('<p></p>', $faker->paragraphs(1)) . '</p>')
                ->setPrivate(true);

        $manager->persist($admin);

        
        $moderators = [];
        for ($m = 1; $m <= 4; $m++) {
            $moderator = new User();
            $hash = $this->passwordHasher->hashPassword($moderator, 'password');
            
            $moderator->setPseudo($faker->name())
            ->setRoles(['ROLE_MODERATOR'])
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
            ->setPrivate(true);
            
            $manager->persist($moderator);
            
            $moderators[] = $moderator;
        }
        
        $users = []; // Array to store users
        $userCount = 20; // Number of users to create
        
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
            ->setAvatar('')
            ->setBanner('')
                ->setPrivate($faker->boolean());
                
                $manager->persist($user);
                $users[] = $user; // Add user to the array
            }
            
        //create Delivery options
        $deliveryOptions = [
            [
                'name' => 'Standard Shipping',
                'price' => 5.99,
                'deliveryTime' => "2 weeks"
            ],
            [
                'name' => 'Express Shipping',
                'price' => 12.99,
                'deliveryTime' => "3 days" 
                ]
            ];
            
            foreach ($deliveryOptions as $optionData) {
                $deliveryOption = new Delivery();
                $deliveryOption->setName($optionData['name'])
                ->setPrice($optionData['price'])
                ->setDeliveryTime($optionData['deliveryTime']);
                
                $manager->persist($deliveryOption);
            }
                
            // Create colors
        $colors = [];
        $colorNames = ['Red', 'Green', 'Blue', 'Black', 'White', 'Yellow', 'Purple'];
        foreach ($colorNames as $colorName) {
            $color = new ProductColor();
            $color->setName($colorName);
            $color->setHexCode($faker->hexColor());
            
            $manager->persist($color);
            $colors[] = $color; // Store colors to use later
        }
        
        $categories = [];
        for ($c = 1; $c <= 10; $c++) {
            $category = new ProductCategory();
            $category->setName($faker->word());
            $manager->persist($category);
            $categories[] = $category;
        }

        
        $avatars = [
            'https://i.pravatar.cc/150?img=1',
            'https://i.pravatar.cc/150?img=2',
            'https://i.pravatar.cc/150?img=3',
            'https://i.pravatar.cc/150?img=4',
            'https://i.pravatar.cc/150?img=5',
            'https://i.pravatar.cc/150?img=6',
            'https://i.pravatar.cc/150?img=7',
            'https://i.pravatar.cc/150?img=8',
            'https://i.pravatar.cc/150?img=9',
        ];

        $levels = [1, 2, 2, 2, 2, 3, 3, 3, 3];

        $networkOptions = ['instagram', 'linkedin'];

        for ($t = 1; $t <= 9; $t++) {
            $team = new Team();

            $socialNetwork = $faker->randomElement($networkOptions);

            $team->setName($faker->name())
                ->setFonction($faker->word())
                ->setLevel($levels[$t - 1])
                ->setAvatar($avatars[$t - 1]);

                if ($socialNetwork === 'instagram') {
                    $team->setInstagram('https://instagram.com/' . $faker->userName());
                    $team->setLinkedin(null);
                } else {
                    $team->setInstagram(null);
                    $team->setLinkedin('https://linkedin.com/in/' . $faker->userName());
                }

            $manager->persist($team);
        }

        $productDeleted = new Product();

        $productDeleted->setReference('XXXXXXXXXXXXXXXXXXXXXXXX')
        ->setName('Deleted Product')
                        ->setDescription('This product has been deleted from our store.')
                        ->setPrice('0')
                        ->setColor($colors[array_rand($colors)]);
                        
                        $manager->persist($productDeleted);
                        
                        $variantDeleted = new ProductVariant();
                        $variantDeleted->setSize(0)
                        ->setStock(0)
                        ->setProduct($productDeleted); // Associate with ProductDeleted
    
        $manager->persist($variantDeleted);

        $productCount = 50;

        for ($i = 0; $i < $productCount; $i++) {
            $product = new Product();
            $product->setReference($faker->unique()->ean13())
                    ->setName($faker->words(3, true))
                    ->setDescription($faker->paragraph())
                    ->setPrice($faker->randomFloat(2, 5, 500))
                    ->setColor($colors[array_rand($colors)]);
            
            $categoriesAssociated = $faker->randomElements($categories, $faker->numberBetween(1, 3));

            foreach ($categoriesAssociated as $category) {
                $product->addProductCategory($category);
            }

            $reviews = [];

            $review = new Review();
            $review->setAuthor($users[rand(0, count($users)-1)])
                    ->setProduct($product)
                    ->setTimestamp($faker->dateTimeBetween('-1 year', '-1 month')) 
                    ->setContent($faker->paragraph())
                    ->setRating(rand(1,5));
            $manager->persist($review);
            $reviews[] = $review;
        
            $manager->persist($product);

            // Création des variantes pour chaque produit
            $variantCount = rand(1, 5); // Nombre de variantes par produit
            for ($v = 0; $v < $variantCount; $v++) {
                $variant = new ProductVariant();
                $variant->setSize($faker->numberBetween(36, 48))
                        ->setStock($faker->numberBetween(0, 100))
                        ->setProduct($product);
                
                $manager->persist($variant);
            }
        }
    
        // Create posts
        $posts = [];
        for ($i = 1; $i <= 40; $i++) {
            $post = new Post();
            $post->setTitle($faker->sentence())
                ->setDescription('<p>' . join('</p><p>', $faker->paragraphs(2)) . '</p>')
                ->setTimestamp($faker->dateTimeBetween('-1 year', '-1 month'))
                ->setAuthor($users[array_rand($users)]) // Random author
                ->setCommentDisabled($faker->boolean(30)); // 30% chance to disable comments
            $manager->persist($post);
            $posts[] = $post;
        }
    
        // Create comments and replies
        $comments = []; // Array to store comments
        foreach ($posts as $post) {
            // Check if comments are disabled
            if ($post->isCommentDisabled()) {
                continue; // Skip comment creation for this post
            }
    
            $commentCount = rand(2, 30); // Each post gets between 2 to 15 comments
    
            for ($c = 0; $c < $commentCount; $c++) {
                $comment = new Comment();
                $comment->setContent($faker->paragraph())
                        ->setTimestamp($faker->dateTimeBetween('-1 year', 'now'))
                        ->setAuthor($users[array_rand($users)]) // Random user as author
                        ->setPost($post);
                $manager->persist($comment);
                $comments[] = $comment; // Add comment to the array
    
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
                    $comments[] = $reply; // Add reply to the array
                }
            }
        }
    
        // Create likes for posts
        foreach ($posts as $post) {
            $postAuthor = $post->getAuthor();
            $potentialLikers = array_filter($users, fn($user) => $user !== $postAuthor);
            $potentialLikers = array_values($potentialLikers);
    
            for ($l = 0; $l < rand(1, $userCount); $l++) { // Each post gets between 1 to 10 likes
                $like = new Like();
                $like->setUser($potentialLikers[array_rand($potentialLikers)])
                     ->setPost($post);
                $manager->persist($like);
            }
        }
    
        // Create likes for comments
        foreach ($comments as $comment) {
            $commentAuthor = $comment->getAuthor();
            $potentialLikers = array_filter($users, fn($user) => $user !== $commentAuthor);
            $potentialLikers = array_values($potentialLikers);
    
            for ($l = 0; $l < rand(1, 5); $l++) { // Each comment gets between 1 to 5 likes
                $likeComment = new LikeComment();
                $likeComment->setUser($potentialLikers[array_rand($potentialLikers)])
                            ->setComment($comment);
                $manager->persist($likeComment);
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
    
        // Create conversations and messages
        $conversations = []; // Tableau pour stocker les conversations créées
        foreach ($users as $user) {
            // Filter only users that this user can send messages to
            $possibleRecipients = array_filter($users, fn($recipient) => $recipient !== $user); // Exclude self

            // Shuffle the possible recipients to ensure randomness in conversation creation
            shuffle($possibleRecipients);

            // Create a random number of conversations
            $conversationCount = rand(0, count($possibleRecipients)); // Randomize the number of conversations

            for ($i = 0; $i < $conversationCount; $i++) {
                $recipient = $possibleRecipients[$i];
                // Vérifier si une conversation avec les mêmes intervenants existe déjà
                $existingConversation = array_filter($conversations, fn($conversation) => (
                    ($conversation->getSender() === $user && $conversation->getRecipient() === $recipient) ||
                    ($conversation->getSender() === $recipient && $conversation->getRecipient() === $user)
                ));

                if (empty($existingConversation)) {
                    $conversation = new Conversation();
                    $conversation->setSender($user);
                    $conversation->setRecipient($recipient);

                    // Randomly decide if the conversation is accepted or not
                    if ($faker->boolean) {
                        $conversation->setAccepted(true); // Set conversation as accepted (in progress)
                        // Create multiple messages for active conversations
                        $messageCount = rand(2, 10); // Randomize the number of messages
                        for ($m = 0; $m < $messageCount; $m++) {
                            $message = new Message();
                            $message->setConversation($conversation);
                            $message->setSender($faker->boolean ? $user : $recipient); // Randomly set sender
                            $message->setContent($faker->sentence());
                            $message->setTimestamp($faker->dateTimeBetween('-1 year', 'now'));
                            $message->setRead($faker->boolean(70));
                            $manager->persist($message);
                        }
                    } else {
                        $conversation->setAccepted(false); // Set conversation as a request
                        // Create a single message for the request
                        $message = new Message();
                        $message->setConversation($conversation);
                        $message->setSender($user); // User who initiated the request
                        $message->setContent($faker->sentence());
                        $message->setTimestamp($faker->dateTimeBetween('-1 year', 'now'));
                        $message->setRead(false);
                        $manager->persist($message);
                    }

                    $manager->persist($conversation);
                    $conversations[] = $conversation; // Ajouter la conversation au tableau
                }
            }
        }

        //contact
        for ($c=1; $c <=10 ; $c++){
            $contact = new Contact();
            $contact->setName($faker->name())
                    ->setEmail($faker->email())
                    ->setMessage('<p>'.join('<p></p>',$faker->paragraphs(2)).'</p>');

            $manager->persist($contact);
        }
    
        $manager->flush();
    }
    
}
