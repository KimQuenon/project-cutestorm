<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Product;
use App\Entity\CartItem;
use App\Form\ProductType;
use App\Form\AddToCartType;
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

        if($form->isSubmitted() && $form->IsValid())
        {
            foreach ($product->getProductVariants() as $variant) {
                $manager->persist($variant);
            }

            $manager->persist($product);

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
    public function show(#[MapEntity(mapping: ['slug' => 'slug'])] Product $product, ProductRepository $productRepo, Request $request, EntityManagerInterface $manager): Response
    {
        $user = $this->getUser();
        $cart = $user->getCart()->first();

        if (!$cart) {
            $cart = new Cart();
            $user->addCart($cart);

            $manager->persist($cart);
            $manager->flush();
        }

        $form = $this->createForm(AddToCartType::class, null, [
            'product_variants' => $product->getProductVariants(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $cartItem = new CartItem();
            $cartItem->setProductVariant($data['productVariant']);
            $cartItem->setQuantity($data['quantity']);
            $cartItem->setCart($cart);

            $manager->persist($cartItem);
            $manager->flush();

            $this->addFlash('success', 'Item added to cart');

            return $this->redirectToRoute('product_show', [
                'slug' => $product->getSlug()
            ]);
        }

        return $this->render('products/show.html.twig', [
            'product' => $product,
            'myForm' => $form->createView(),
        ]);
    }

    #[Route('/product/{slug}/edit', name: 'product_edit')]
    public function edit(ProductRepository $productRepo, Request $request, EntityManagerInterface $manager, Product $product): Response
    {
        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($product->getProductVariants() as $variant) {
                $manager->persist($variant);
            }

            $manager->persist($product);
            $manager->flush();

            $this->addFlash(
                'success',
                "La fiche de <strong>".$product->getName()."</strong> a été mise à jour."
            );

            return $this->redirectToRoute('product_show', [
                'slug' => $product->getSlug()
            ]);
        }

        return $this->render('products/edit.html.twig', [
            'myForm' => $form->createView(),
        ]);
    }
}
