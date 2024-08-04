<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ProductColorRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    #[Route('/store', name: 'products_index')]
    public function index(ProductRepository $productRepo): Response
    {
        $products = $productRepo->findAll();
        return $this->render('products/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/product/new', name: 'product_create')]
    public function create(ProductColorRepository $colorRepo, Request $request, EntityManagerInterface $manager): Response
    {

        $product = new Product();
        $form = $this->createform(ProductType::class, $product);
        $colors = $colorRepo->findAll();

        $form->handleRequest($request);

        //form complet et valid -> envoi bdd + message et redirection
        if($form->isSubmitted() && $form->IsValid())
        {
            $manager->persist($product);

            // foreach ($artwork->getMovements() as $movement)
            // {
            //     $movement->addArtwork($artwork);
            //     $manager->persist($artwork);
            // }


            $manager->flush();

            $this->addFlash(
                'success',
                "La fiche de <strong>".$product->getName()."</strong> a bien été enregistrée."
            );

            return $this->redirectToRoute('product_show', [
                'slug'=> $product->getSlug()
            ]);
        }

        return $this->render('products/new.html.twig', [
            'myForm' => $form->createView(),
            'colors' => $colors
        ]);
    }

    #[Route('/product/{slug}', name: 'product_show')]
    public function show(#[MapEntity(mapping: ['slug' => 'slug'])] Product $product, ProductRepository $productRepo): Response
    {

        return $this->render('products/show.html.twig', [
            'product' => $product,
        ]);
    }
}
