<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Order;
use Stripe\Checkout\Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PaymentController extends AbstractController
{
    #[Route('/order/payment/{reference}', name: 'payment_stripe')]
    public function index(#[MapEntity(mapping: ['reference' => 'reference'])] Order $order): RedirectResponse
    {
        
        Stripe::setApiKey('sk_test_51PkPygAPzfXxdZQct3F7TCdFgbR0MKHN1U1DT3HmzD9c95rGTPadyeEtvn6eeZbM0csfdeXDlzPSZxoH2b3u58pb00zFv496lV');
        // header('Content-Type: application/json');

        // $YOUR_DOMAIN = 'http://localhost:4242';

        $checkout_session = Session::create([
        'line_items' => [[
            # Provide the exact Price ID (e.g. pr_1234) of the product you want to sell
            'price' => '{{PRICE_ID}}',
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => $YOUR_DOMAIN . '/success.html',
        'cancel_url' => $YOUR_DOMAIN . '/cancel.html',
        ]);

    }
}
