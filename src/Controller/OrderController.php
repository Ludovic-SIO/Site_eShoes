<?php

namespace App\Controller;

use App\Entity\Order;
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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class OrderController extends AbstractController
{
    public function __construct(private MailerInterface $mailer) {}

    #[Route('/order', name: 'app_order')]
    public function index(
        Request $request,
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        StripePayment $stripePayment
    ): Response {
        $cart = $session->get('cart', []);
        $cartWithData = [];
        $total = 0;

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

            // Redirection vers Stripe
            $stripePayment->startPayment($order);
            $stripeRedirectUrl = $stripePayment->getStripeRedirectUrl();
            return $this->redirect($stripeRedirectUrl);
        }

        return $this->render('order/index.html.twig', [
            'form' => $form,
            'total' => $total,
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/order/success', name: 'app_order_success')]
    public function orderSuccess(
        Request $request,
        StripePayment $stripePayment,
        EntityManagerInterface $entityManager
    ): Response {
        $sessionId = $request->query->get('session_id');
        if (!$sessionId) {
            throw $this->createNotFoundException('Session Stripe manquante.');
        }

        $session = $stripePayment->retrieveSession($sessionId);

        if ($session->payment_status === 'paid') {
            $orderId = $session->metadata->order_id;
            $order = $entityManager->getRepository(Order::class)->find($orderId);

            if (!$order) {
                throw $this->createNotFoundException('Commande introuvable.');
            }

            // Vider le panier
            $request->getSession()->remove('cart');

            // Générer PDF
            $pdfOptions = new Options();
            $pdfOptions->set('defaultFont', 'Arial');
            $domPdf = new Dompdf($pdfOptions);
            $html = $this->renderView('bill/index.html.twig', ['order' => $order]);
            $domPdf->loadHtml($html);
            $domPdf->setPaper('A4', 'portrait');
            $domPdf->render();
            $pdfContent = $domPdf->output();

            // Envoyer l'email
            $recipient = $this->getUser()?->getEmail() ?? 'jdubromelle@edouardgand.fr';
            $htmlMail = $this->renderView('mail/orderConfirm.html.twig', ['order' => $order]);

            $email = (new Email())
                ->from('sio-shoes@edouardgand.fr')
                ->to($recipient)
                ->subject('Confirmation de commande Sio-Shoes')
                ->html($htmlMail)
                ->attach($pdfContent, sprintf('facture-%d.pdf', $order->getId()), 'application/pdf');

            try {
                $this->mailer->send($email);
                $this->addFlash('success', 'Paiement réussi et email envoyé.');
            } catch (\Throwable $e) {
                $this->addFlash('danger', 'Impossible d\'envoyer l\'email : ' . $e->getMessage());
            }

            return $this->render('order/success.html.twig', ['order' => $order]);
        }

        throw $this->createAccessDeniedException('Paiement non confirmé.');
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
    public function getAllOrders(OrderRepository $orderRepository, CategoryRepository $categoryRepository): Response
    {
        $orders = $orderRepository->findAll();

        return $this->render('order/orders.html.twig', [
            'categories' => $categoryRepository->findAll(),
            'orders' => $orders,
        ]);
    }

    #[Route('/editor/orders/{id}/is-delivered/update', name: 'app_orders_is_delivered_update')]
    public function isDeliveredUpdate(int $id, OrderRepository $orderRepository, EntityManagerInterface $entityManager): Response
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
    public function delete(Order $order, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($order);
        $entityManager->flush();

        $this->addFlash('danger', 'Commande supprimée avec succès.');
        return $this->redirectToRoute('app_orders', [], Response::HTTP_SEE_OTHER);
    }
}
