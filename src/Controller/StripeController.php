<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StripeController extends AbstractController
{
    #[Route('/stripe', name: 'app_stripe')]
    public function index(): Response
    {
        return $this->render('stripe/index.html.twig', [
            'controller_name' => 'StripeController',
        ]);
    }

    #[Route('/pay/success', name: 'app_stripe_success')]
    public function success(CategoryRepository $categoryRepository): Response
    {
        return $this->render('stripe/success.html.twig', [
            'controller_name' => 'StripeController',
            'categories' => $categoryRepository->findAll(),
        ]);
    }
 
    #[Route('/pay/cancel', name: 'app_stripe_cancel')]
    public function cancel(CategoryRepository $categoryRepository): Response
    {
            return $this->render('stripe/cancel.html.twig', [
                    'controller_name' => 'StripeController',
                    'categories' => $categoryRepository->findAll(),
            ]);
    }

    
}
