<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class SearchService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function search(string $query, string $entityType): array
    {
        switch ($entityType) {
            case 'product':
                return $this->searchProducts($query);
            case 'post':
                return $this->searchPosts($query);
            default:
                return [];
        }
    }

    private function searchProducts(string $query): array
    {
        $repository = $this->entityManager->getRepository('App\Entity\Product');
        $results = $repository->findByProductNameQuery($query)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return array_map(function ($product) {
            return [
                'name' => $product->getName(),
                'slug' => $product->getSlug(),
            ];
        }, $results);
    }

    private function searchPosts(string $query): array
    {
        $repository = $this->entityManager->getRepository('App\Entity\Post');
        $results = $repository->findByTitleOrPseudoQuery($query)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return array_map(function ($post) {
            return [
                'title' => $post->getTitle(),
                'author' => $post->getAuthor()->getPseudo(),
                'slug' => $post->getSlug(),
            ];
        }, $results);
    }
}
