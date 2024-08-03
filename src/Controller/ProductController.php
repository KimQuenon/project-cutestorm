<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    #[Route('/store', name: 'products_index')]
    public function index(ProductRepository $productRepo): Response
    {
        $products= $productRepo->findAll();
        return $this->render('products/index.html.twig', [
            'products' => $products,
        ]);
    }
}
