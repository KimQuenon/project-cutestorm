<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Product;
use App\Entity\CartItem;
use App\Form\ProductType;
use App\Form\AddToCartType;
use App\Repository\ProductRepository;
use App\Repository\CartItemRepository;
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
        $form = $this->createForm(ProductType::class, $product);
        $colors = $colorRepo->findAll();
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $variants = $product->getProductVariants();
            $uniqueVariants = [];
    
            // Combine stocks of variants with the same size
            foreach ($variants as $variant) {
                $size = $variant->getSize();
                if (!isset($uniqueVariants[$size])) {
                    $uniqueVariants[$size] = $variant;
                } else {
                    $existing = $uniqueVariants[$size];
                    $existing->setStock($existing->getStock() + $variant->getStock());
                    $product->removeProductVariant($variant);
                }
            }
    
            // Persist unique variants
            foreach ($uniqueVariants as $variant) {
                $manager->persist($variant);
            }
    
            $manager->persist($product);
            $manager->flush();
    
            $this->addFlash(
                'success',
                "Product <strong>{$product->getName()}</strong> has been saved."
            );
    
            return $this->redirectToRoute('product_show', [
                'slug' => $product->getSlug()
            ]);
        }
    
        return $this->render('products/new.html.twig', [
            'myForm' => $form->createView(),
            'colors' => $colors
        ]);
    }
    
    

    #[Route('/product/{slug}', name: 'product_show')]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] Product $product,
        ProductRepository $productRepo,
        CartItemRepository $cartItemRepo,
        Request $request,
        EntityManagerInterface $manager
    ): Response {
        $user = $this->getUser();
        $cart = null;
    
        if ($user) {
            $cart = $user->getCart();
            if (!$cart) {
                $cart = new Cart();
                $user->setCart($cart);
                $manager->persist($cart);
                $manager->flush();
            }
        }
    
        $form = $this->createForm(AddToCartType::class, null, [
            'product_variants' => $product->getProductVariants(),
        ]);
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $productVariant = $data['productVariant'];
            $quantity = $data['quantity'];
    
            // Check stock availability
            if ($quantity > $productVariant->getStock()) {
                $this->addFlash('danger', 'Not enough stock available.');
                return $this->redirectToRoute('product_show', [
                    'slug' => $product->getSlug(),
                ]);
            }
    
            if ($user) {
                // Check if the cart already contains this product variant
                $cartItem = $cartItemRepo->findOneBy([
                    'cart' => $cart,
                    'productVariant' => $productVariant,
                ]);
    
                if ($cartItem) {
                    // If the cart item exists, update the quantity
                    $newQuantity = $cartItem->getQuantity() + $quantity;
    
                    if ($newQuantity > $productVariant->getStock()) {
                        $this->addFlash('danger', 'Not enough stock available for the requested quantity.');
                        return $this->redirectToRoute('product_show', [
                            'slug' => $product->getSlug(),
                        ]);
                    }
    
                    $cartItem->setQuantity($newQuantity);
                } else {
                    $cartItem = new CartItem();
                    $cartItem->setProductVariant($productVariant);
                    $cartItem->setQuantity($quantity);
                    $cartItem->setCart($cart);
    
                    $manager->persist($cartItem);
                }
    
                $manager->flush();
                $this->addFlash('success', 'Item added to cart. <a href="' . $this->generateUrl('cart_show') . '">View Cart</a>');
            }
    
            return $this->redirectToRoute('product_show', [
                'slug' => $product->getSlug(),
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
