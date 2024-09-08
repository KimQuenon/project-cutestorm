<?php

namespace App\Controller;

use App\Entity\Order;
use App\Service\PaginationService;
use App\Repository\OrderRepository;
use App\Service\PdfGeneratorService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminOrderController extends AbstractController
{
    #[Route('/admin/orders/{page<\d+>?1}', name: 'admin_orders_index')]
    public function index(int $page, OrderRepository $orderRepo, PaginationService $paginationService): Response
    {
        $orders = $orderRepo->findBy([], ['timestamp' => 'DESC']);
        $total = $orderRepo->getTotalPrice();

        $currentPage = $page;
        $itemsPerPage = 12;
    
        $pagination = $paginationService->paginate($orders, $currentPage, $itemsPerPage);
        $ordersPaginated = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        return $this->render('admin/orders/index.html.twig', [
            'orders' => $ordersPaginated,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'total' => $total
        ]);
    }

    #[Route('/admin/orders/paid/{page<\d+>?1}', name: 'admin_orders_paid')]
    public function paid(int $page, OrderRepository $orderRepo, PaginationService $paginationService): Response
    {
        $orders = $orderRepo->findAllPaidOrders();
        $total = $orderRepo->getTotalPrice();
        $totalPaid = $orderRepo->getTotalPaidOrdersPrice();

        $currentPage = $page;
        $itemsPerPage = 9;
    
        $pagination = $paginationService->paginate($orders, $currentPage, $itemsPerPage);
        $ordersPaginated = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        return $this->render('admin/orders/paid.html.twig', [
            'orders' => $ordersPaginated,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'total' => $total,
            'totalPaid' => $totalPaid
        ]);
    }

    #[Route('/admin/orders/unpaid/{page<\d+>?1}', name: 'admin_orders_unpaid')]
    public function unpaid(int $page, OrderRepository $orderRepo, PaginationService $paginationService): Response
    {
        $orders = $orderRepo->findAllUnpaidOrders();
        $total = $orderRepo->getTotalPrice();
        $totalUnpaid = $orderRepo->getTotalUnpaidOrdersPrice();

        $currentPage = $page;
        $itemsPerPage = 9;
    
        $pagination = $paginationService->paginate($orders, $currentPage, $itemsPerPage);
        $ordersPaginated = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        return $this->render('admin/orders/unpaid.html.twig', [
            'orders' => $ordersPaginated,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'total' => $total,
            'totalUnpaid' => $totalUnpaid
        ]);
    }

    #[Route('admin/order/{reference}/pdf', name: 'admin_order_pdf')]
    #[IsGranted('ROLE_USER')]
    public function generatePdf(#[MapEntity(mapping: ['reference' => 'reference'])] Order $order, PdfGeneratorService $pdfGeneratorService): Response
    {

        $html = $this->renderView('orders/pdf.html.twig', [
            'order' => $order,
            'user' => $order->getUser()
        ]);

        $userName = $order->getUser()->getLastName();

        $fileName = sprintf('Order_%s-%s.pdf', $order->getReference(), $userName);

        return $pdfGeneratorService->generatePdf($html, $fileName);
    }
}
