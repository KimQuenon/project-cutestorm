<?php

namespace App\Controller;

use App\Entity\CartItem;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ProductVariantRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'cart_show')]
    public function index(): Response
    {
        $user = $this->getUser();
        $cart = $user->getCart();

        $cartItems = $cart->getCartItems();

        return $this->render('cart/index.html.twig', [
            'cartItems' => $cartItems,
        ]);
    }
    
    #[Route('/cart/clear', name: 'cart_clear')]
    public function clearCart(EntityManagerInterface $manager): RedirectResponse
    {
        $user = $this->getUser();

        $cart = $user->getCart();
        if ($cart) {
            foreach ($cart->getCartItems() as $cartItem) {
                $manager->remove($cartItem);
            }
            $manager->flush();
            $this->addFlash('success', 'Cart has been cleared.');
        }

        return $this->redirectToRoute('cart_show');
    }

    #[Route('/cart/edit/{id}', name: 'cart_edit', methods: ['POST'])]
    public function editItem(#[MapEntity(mapping: ['id' => 'id'])] CartItem $cartItem, ProductVariantRepository $productVariantRepo, Request $request, EntityManagerInterface $manager): RedirectResponse
    {
        $user = $this->getUser();
        $cart = $user->getCart();
    
        if ($cartItem->getCart() !== $cart) {
            $this->addFlash('danger', 'You do not have permission to edit this item.');
            return $this->redirectToRoute('cart_show');
        }

        $quantity = $request->request->get('quantity');

        if ($quantity !== null && $quantity > 0) {
            $productVariant = $cartItem->getProductVariant();
            $availableStock = $productVariant->getStock();

            if ($quantity <= $availableStock) {
                $cartItem->setQuantity($quantity);
                $manager->persist($cartItem);
                $manager->flush();

                $this->addFlash('success', 'Cart item updated.');
            } else {
                $this->addFlash('danger', 'Requested quantity exceeds available stock.');
            }
        } else {
            $this->addFlash('danger', 'Invalid quantity.');
        }

        return $this->redirectToRoute('cart_show');
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove')]
    public function removeItem(#[MapEntity(mapping: ['id' => 'id'])] CartItem $cartItem, EntityManagerInterface $manager): RedirectResponse
    {
        $user = $this->getUser();
        $cart = $user->getCart();
    
        // Vérifiez si l'élément appartient au panier de l'utilisateur connecté
        if ($cartItem->getCart() !== $cart) {
            $this->addFlash('danger', 'You do not have permission to remove this item.');
            return $this->redirectToRoute('cart_show');
        }
    
        $cart->removeCartItem($cartItem);
        $manager->remove($cartItem);
        $manager->flush();
    
        $this->addFlash('success', 'Item removed from cart.');
    
        return $this->redirectToRoute('cart_show');
    }


}
