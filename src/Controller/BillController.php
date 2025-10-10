<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BillController extends AbstractController
{
    #[Route('/editor/order/{id}/bill', name: 'app_bill')]
    public function index(int $id, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Commande non trouvée.');
        }

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        
    
        $domPdf = new Dompdf($pdfOptions);

        $html = $this->renderView('bill/index.html.twig', [
            'order' => $order,
        ]);
        $domPdf->loadHtml($html);
        $domPdf->setPaper('A4', 'portrait');
        $domPdf->render();
        $domPdf->stream('facture-' . $order->getId() . '.pdf', [
            'Attachment' => false
        ]);

        return new Response(); 
    }
}
