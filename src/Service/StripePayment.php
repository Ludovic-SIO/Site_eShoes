<?php
namespace App\Service;
 
use Stripe\Stripe;
use Stripe\Checkout\Session;
 
class StripePayment
{
    private string $redirectUrl;
 
    public function __construct()
    {
        Stripe::setApiKey($_SERVER['STRIPE_SECRET']);
        Stripe::setApiVersion('2025-07-30.basil');
    }
 
    public function startPayment($order): void
    {
        $orderProducts = $order->getOrderProducts();
        $products = [];
 
        foreach ($orderProducts as $orderProduct) {
            $products[] = [
                'name' => $orderProduct->getProduct()->getName(),
                'qte' => $orderProduct->getQte(),
                'price' => $orderProduct->getPrice(),
            ];
        }
 
        $products[] = [
            'name' => 'Frais de livraison',
            'qte' => 1,
            'price' => $order->getShippingPrice(),
        ];
 
        $lineItems = array_map(fn($product) => [
            'quantity' => $product['qte'],
            'price_data' => [
                'currency' => 'EUR',
                'product_data' => [
                    'name' => $product['name'],
                ],
                'unit_amount' => (int)($product['price'] * 100), // Stripe prend les prix en centimes
            ],
        ], $products);
 
        $session = Session::create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'cancel_url' => 'http://localhost:8000/pay/cancel',
            'success_url' => 'http://localhost:8000/pay/success',
            'billing_address_collection' => 'required',
            'shipping_address_collection' => [
                'allowed_countries' => ['FR'],
            ],
            'metadata' => [],
        ]);
 
        $this->redirectUrl = $session->url;
    }
 
    public function getStripeRedirectUrl(): string
    {
        return $this->redirectUrl;
    }
}