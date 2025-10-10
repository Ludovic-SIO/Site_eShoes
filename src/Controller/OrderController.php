<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\OrderProducts;
use App\Form\OrderType;
use App\Service\StripePayment;
use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\CityRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class OrderController extends AbstractController
{
    public function __construct(private MailerInterface $mailer) {}

    #[Route('/order', name: 'app_order')]
    public function index(
        Request $request,
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        SessionInterface $session,
        EntityManagerInterface $entityManager
    ): Response {
        $cart = $session->get('cart', []);
        $cartWithData = [];
        $total = 0;

        // Préparer les données du panier
        foreach ($cart as $id => $quantity) {
            $product = $productRepository->find($id);
            if ($product) {
                $cartWithData[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                ];
                $total += $product->getPrice() * $quantity;
            }
        }

        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

      
        if ($form->isSubmitted() && $form->isValid() && !empty($cart)) {
            $order->setCreatedAt(new \DateTimeImmutable());
            $order->setTotalPrice($total + $order->getShippingPrice());

            $entityManager->persist($order);
            $entityManager->flush();

           
            foreach ($cart as $id => $quantity) {
                $product = $productRepository->find($id);

                if ($product) {
                    $orderProduct = new OrderProducts();
                    $orderProduct->setOrder($order);
                    $orderProduct->setProduct($product);
                    $orderProduct->setQte($quantity);
                    $orderProduct->setPrice($product->getPrice());

                    $order->addOrderProduct($orderProduct);
                    $entityManager->persist($orderProduct);
                }
            }

            $entityManager->flush();

            $session->remove('cart');

            //Paiement avec Stripe
 
            $payment = new StripePayment();
            $payment->startPayment($order);
            $stripeRedirectUrl = $payment->getStripeRedirectUrl();
            return $this->redirect($stripeRedirectUrl);

            // Envoi d'un e-mail de confirmation
            $html = $this->renderView('mail/orderConfirm.html.twig', [
                'order' => $order,
            ]);

            $email = (new Email())
                ->from('sio-shoes@edouardgand.fr')
                ->to('jdubromelle@edouardgand.fr') // à remplacer par email utilisateur
                ->subject('Confirmation de commande Sio-Shoes')
                ->html($html);

            $this->mailer->send($email);

            return $this->redirectToRoute('order_message');
        }

        // Affichage de la page de commande
        return $this->render('order/index.html.twig', [
            'form' => $form,
            'total' => $total,
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/get_shipping_cost', name: 'get_shipping_cost', methods: ['POST'])]
    public function getShippingCost(Request $request, CityRepository $cityRepository): JsonResponse
    {
        $cityId = $request->request->get('city');
        $city = $cityRepository->find($cityId);
        $cost = $city ? $city->getShippingCost() : 10.00;

        return new JsonResponse(['shippingCost' => $cost]);
    }

    #[Route('/order-message', name: 'order_message')]
    public function orderMessage(CategoryRepository $categoryRepository): Response
    {
        return $this->render('order/order-message.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/editor/orders', name: 'app_orders')]
    public function getAllOrders(
        OrderRepository $orderRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $orders = $orderRepository->findAll();

        return $this->render('order/orders.html.twig', [
            'categories' => $categoryRepository->findAll(),
            'orders' => $orders,
        ]);
    }

    #[Route('/editor/orders/{id}/is-delivered/update', name: 'app_orders_is_delivered_update')]
    public function isDeliveredUpdate(int $id,OrderRepository $orderRepository,EntityManagerInterface $entityManager): Response 
    {
        $order = $orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        $order->setIsDelivered(true);
        $entityManager->flush();

        $this->addFlash('success', 'La commande a été marquée comme livrée.');

        return $this->redirectToRoute('app_orders');
    }


    #[Route('/editor/orders/{id}/delete', name: 'app_delete')]
    public function delete(Order $order,EntityManagerInterface $entityManager): Response
    {

        
            $entityManager->remove($order);
            $entityManager->flush();

            $this->addFlash('danger', 'Sous-catégorie supprimée avec succès.');

            return $this->redirectToRoute('app_orders', [], Response::HTTP_SEE_OTHER);
    }

       
    
}
