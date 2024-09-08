<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use App\Entity\OrderItem;
use App\Service\PaginationService;
use App\Repository\OrderRepository;
use App\Service\PdfGeneratorService;
use App\Repository\DeliveryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OrderController extends AbstractController
{
    #[Route('/profile/orders/{page<\d+>?1}', name: 'orders_index')]
    #[IsGranted('ROLE_USER')]
    public function index(int $page, PaginationService $paginationService, OrderRepository $orderRepo): Response
    {
        $user = $this->getUser();
        $orders = $orderRepo->findBy(['user' => $user], ['timestamp' => 'DESC']);

        $currentPage = $page;
        $itemsPerPage = 12;

        $pagination = $paginationService->paginate($orders, $currentPage, $itemsPerPage);
        $ordersPaginated = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        return $this->render('orders/index.html.twig', [
            'orders' => $ordersPaginated,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/order/create', name: 'order_create')]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, DeliveryRepository $deliveryRepo, OrderRepository $orderRepo, EntityManagerInterface $manager): Response
    {
        $user = $this->getUser();
        $cart = $user->getCart();
        $cartItems = $cart->getCartItems();

        $unpaidOrders = $orderRepo->findUnpaidOrders($user);

        if ($unpaidOrders) {
            $this->addFlash('warning', 'You have unpaid invoices. Please settle them before placing a new order.');
            return $this->redirectToRoute('orders_index');
        }
    
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

    #[Route('/profile/order/{reference}', name: 'order_show')]
    #[IsGranted('ROLE_USER')]
    public function show(#[MapEntity(mapping: ['reference' => 'reference'])] Order $order): Response
    {
        $user = $this->getUser();

        $orderItems = $order->getOrderItems();
    
        return $this->render('orders/show.html.twig', [
            'order' => $order,
            'orderItems' => $orderItems,
        ]);
    }
    
    #[Route('profile/order/{reference}/pdf', name: 'order_pdf')]
    #[IsGranted('ROLE_USER')]
    public function generatePdf(#[MapEntity(mapping: ['reference' => 'reference'])] Order $order, PdfGeneratorService $pdfGeneratorService): Response
    {
        $user = $this->getUser();

        if ($order->getUser() !== $user) {
            $this->addFlash(
                'danger',
                "You do not have permission to access this."
            );
            return $this->redirectToRoute('products_index');
        }

        $html = $this->renderView('orders/pdf.html.twig', [
            'order' => $order,
            'user' => $user
        ]);

        $userName = $user->getLastName();

        $fileName = sprintf('Order_%s-%s.pdf', $order->getReference(), $userName);

        return $pdfGeneratorService->generatePdf($html, $fileName);
    }
}

