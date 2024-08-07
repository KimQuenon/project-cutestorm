<?php

namespace App\Controller;

use App\Entity\ProductVariant;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ProductVariantRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class StockController extends AbstractController
{
    #[Route('admin/stock/{page<\d+>?1}', name: 'stock_index')]
    public function index(int $page, ProductVariantRepository $variantRepo, PaginationService $paginationService, Request $request, EntityManagerInterface $manager): Response
    {
        $variants = $variantRepo->findBy([], ['stock' => 'ASC']);

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            foreach ($data['stocks'] as $id => $stockChange) {
                $variant = $variantRepo->find($id);
                if ($variant !== null) {
                    $currentStock = $variant->getStock();
                    $newStock = $currentStock + (int)$stockChange;

                    if ($newStock !== $currentStock) {
                        $variant->setStock($newStock);
                        $manager->persist($variant);
                    }
                }
            }

            $manager->flush();

            $this->addFlash('success', 'Stocks updated successfully.');
            return $this->redirectToRoute('stock_index');
        }

        $currentPage = $page;
        $itemsPerPage = 9;
    
        $pagination = $paginationService->paginate($variants, $currentPage, $itemsPerPage);
        $variantsPaginated = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        return $this->render('admin/stock/index.html.twig', [
            'variants' => $variantsPaginated,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }
}
