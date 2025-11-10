<?php
namespace App\Service;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Entity\Order;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripePayment
{
    private string $redirectUrl;
    private UrlGeneratorInterface $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
        Stripe::setApiKey($_ENV['STRIPE_SECRET']);
        Stripe::setApiVersion('2025-07-30.basil');
    }

    public function startPayment(Order $order): void
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
                'unit_amount' => (int)($product['price'] * 100),
            ],
        ], $products);

        $session = Session::create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'cancel_url' => $this->router->generate('app_order', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'success_url' => $this->router->generate('app_order_success', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'billing_address_collection' => 'required',
            'shipping_address_collection' => [
                'allowed_countries' => ['FR'],
            ],
            'metadata' => [
                'order_id' => $order->getId(),
            ],
        ]);

        $this->redirectUrl = $session->url;
    }

    public function getStripeRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    public function retrieveSession(string $sessionId): Session
    {
        return Session::retrieve($sessionId);
    }
}
