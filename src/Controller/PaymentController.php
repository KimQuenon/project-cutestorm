<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Order;
use Stripe\Checkout\Session;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PaymentController extends AbstractController
{
    public function __construct(UrlGeneratorInterface $generator){
        $this->generator = $generator;
    }
    
    #[Route('/order/payment/{reference}', name: 'payment_stripe')]
    #[IsGranted('ROLE_USER')]
    public function index(#[MapEntity(mapping: ['reference' => 'reference'])] Order $order, ProductRepository $productRepo, EntityManagerInterface $manager): RedirectResponse
    {
        if(!$order){
            return $this->redirectToRoute('orders_index');
        }

        $productStripe = [];

        foreach ($order->getOrderItems()->getValues() as $orderItem) {
            $productData = $productRepo->findOneBy(['name' => $orderItem->getProductVariant()->getProduct()->getName()]);

            $productStripe[] = [
                "price_data" => [
                    "currency" => 'USD',
                    "unit_amount" => $productData->getPrice()*100,
                    "product_data" => [
                        "name" => $orderItem->getProductVariant()->getProduct()->getName()
                    ]
                ],
                "quantity" => $orderItem->getQuantity(),
            ];
        
        }
        
        $productStripe[] = [
            "price_data" => [
                "currency" => 'USD',
                "unit_amount" => $order->getDelivery()->getPrice()*100,
                "product_data" => [
                    "name" => $order->getDelivery()->getName()
                    ]
                ],
                "quantity" => 1,
            ];
            
        Stripe::setApiKey('sk_test_51PkPygAPzfXxdZQct3F7TCdFgbR0MKHN1U1DT3HmzD9c95rGTPadyeEtvn6eeZbM0csfdeXDlzPSZxoH2b3u58pb00zFv496lV');

        $checkout_session = Session::create([
            'customer_email' => $this->getUser()->getEmail(),
            'payment_method_types' => ['card'],
            'line_items' => [[
                $productStripe,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generator->generate(
                'payment_stripe_success',
                ['reference' => $order->getReference()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'cancel_url' => $this->generator->generate(
                'payment_stripe_error',
                ['reference' => $order->getReference()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ]);
        
        return new RedirectResponse($checkout_session->url);

    }

    #[Route('/order/payment/{reference}/success', name: 'payment_stripe_success')]
    public function stripeSuccess(#[MapEntity(mapping: ['reference' => 'reference'])] Order $order, EntityManagerInterface $manager): Response
    {
        $order->setPaid(true);
        $manager->flush();

        return $this->render('orders/success.html.twig', [

        ]);
    }

    #[Route('/order/payment/{reference}/error', name: 'payment_stripe_error')]
    public function stripeError(#[MapEntity(mapping: ['reference' => 'reference'])] Order $order): Response
    {

        return $this->render('orders/error.html.twig', [

        ]);
    }
}
