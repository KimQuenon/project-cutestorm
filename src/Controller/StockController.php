<?php

namespace App\Controller;

use App\Entity\ProductVariant;
use App\Repository\ProductVariantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class StockController extends AbstractController
{
    #[Route('/stock', name: 'stock_index')]
    public function index(ProductVariantRepository $variantRepo, Request $request, EntityManagerInterface $manager): Response
    {
        $variants = $variantRepo->findBy([], ['stock' => 'ASC']);

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            foreach ($data['stocks'] as $id => $stockChange) {
                $variant = $variantRepo->find($id);
                if ($variant !== null) {
                    $currentStock = $variant->getStock();
                    $newStock = $currentStock + (int)$stockChange; // Calculate the new stock

                    if ($newStock !== $currentStock) { // Only update if there is a change
                        $variant->setStock($newStock);
                        $manager->persist($variant);
                    }
                }
            }

            $manager->flush();

            $this->addFlash('success', 'Stocks updated successfully.');
            return $this->redirectToRoute('stock_index');
        }

        return $this->render('stock/index.html.twig', [
            'variants' => $variants,
        ]);
    }
}
