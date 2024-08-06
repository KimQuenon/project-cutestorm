<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use App\Entity\OrderItem;
use App\Repository\DeliveryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OrderController extends AbstractController
{
    #[Route('/orders', name: 'orders_index')]
    public function index(): Response
    {
        $user = $this->getUser();
        $orders = $user->getOrders();

        return $this->render('orders/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/order/create', name: 'order_create')]
    public function create(Request $request, DeliveryRepository $deliveryRepo, EntityManagerInterface $manager): Response
    {
        $user = $this->getUser();
        $cart = $user->getCart();
        $cartItems = $cart->getCartItems();
    
        if (count($cartItems) === 0) {
            $this->addFlash('warning', 'Your cart is empty.');
            return $this->redirectToRoute('cart_show');
        }
    
        // Calculate total price of cart items
        $totalPrice = array_reduce($cartItems->toArray(), function($carry, $item) {
            return $carry + ($item->getProductVariant()->getProduct()->getPrice() * $item->getQuantity());
        }, 0);
    
        $order = new Order();
        $order->setTotalPrice($totalPrice);
        $order->setUser($user);
    
        $form = $this->createForm(OrderType::class, $order, [
            'deliveries' => $deliveryRepo->findAll()
        ]);
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Get the selected delivery option
            $selectedDelivery = $form->get('delivery')->getData();
    
            // Add the delivery cost to the total price
            $deliveryCost = $selectedDelivery->getPrice();
            $totalPriceWithDelivery = $totalPrice + $deliveryCost;
            $order->setTotalPrice($totalPriceWithDelivery);
    
            $order->setDelivery($selectedDelivery);
    
            foreach ($cartItems as $cartItem) {
                $productVariant = $cartItem->getProductVariant();

                // Update stock
                $newStock = $productVariant->getStock() - $cartItem->getQuantity();
                if ($newStock < 0) {
                    $this->addFlash('error', 'Insufficient stock for some items.');
                    return $this->redirectToRoute('cart_show');
                }
                $productVariant->setStock($newStock);

                $orderItem = new OrderItem();
                $orderItem->setOrderRelated($order);
                $orderItem->setProductVariant($cartItem->getProductVariant());
                $orderItem->setQuantity($cartItem->getQuantity());
                $order->addOrderItem($orderItem);
            }
    
    
            foreach ($cartItems as $cartItem) {    
                $cart->removeCartItem($cartItem);
            }

            $manager->persist($order);
            $manager->flush();
    
            return $this->redirectToRoute('payment_stripe', [
                'reference'=> $order->getReference()
            ]);
        }
    
        return $this->render('orders/new.html.twig', [
            'form' => $form->createView(),
            'cartItems' => $cartItems,
            'totalPrice' => $totalPrice
        ]);
    }

    #[Route('/order/{reference}', name: 'order_show')]
    public function show(#[MapEntity(mapping: ['reference' => 'reference'])] Order $order): Response
    {
        $orderItems = $order->getOrderItems();
    
        return $this->render('orders/show.html.twig', [
            'order' => $order,
            'orderItems' => $orderItems,
        ]);
    }    
}
