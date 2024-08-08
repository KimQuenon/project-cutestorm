<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Entity\ProductImage;
use App\Form\ProductImageType;
use App\Service\PaginationService;
use App\Repository\ProductRepository;
use App\Repository\OrderItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ProductColorRepository;
use App\Repository\ProductVariantRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class AdminProductController extends AbstractController
{
    #[Route('/admin/products/{page<\d+>?1}', name: 'products_index')]
    public function index(int $page, ProductRepository $productRepo, OrderItemRepository $orderItemRepo, PaginationService $paginationService): Response
    {
        $products = $productRepo->findBy([], ['id' => 'DESC']);
        $bestSeller = $productRepo->findBestSeller();
        $worstSeller = $productRepo->findWorstSeller();

        $currentPage = $page;
        $itemsPerPage = 9;
    
        $pagination = $paginationService->paginate($products, $currentPage, $itemsPerPage);
        $productsPaginated = $pagination['items'];
        $totalPages = $pagination['totalPages'];

        return $this->render('admin/products/index.html.twig', [
            'products' => $productsPaginated,
            'bestSeller' => $bestSeller,
            'worstSeller' => $worstSeller,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('admin/product/new', name: 'product_new')]
    public function new(ProductColorRepository $colorRepo, Request $request, EntityManagerInterface $manager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $colors = $colorRepo->findAll();
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {

            $errors = [];

            if (count($product->getProductImages()) < 1) {
                $errors[] = 'A product must have at least one image.';
            }
    
            if (count($product->getProductVariants()) < 1) {
                $errors[] = 'A product must have at least one variant.';
            }
    
            if ($errors) {
                $this->addFlash('danger', implode(' ', $errors));
                return $this->render("admin/products/new.html.twig", [
                    'myForm' => $form->createView(),
                    'colors' => $colors
                ]);
            }

            foreach ($product->getProductImages() as $image) {
                $file = $image->getFile();
                if ($file) {
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                    try {
                        $file->move(
                            $this->getParameter('uploads_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        return $e->getMessage();
                    }

                    $image->setFilename($newFilename);
                    $image->setProduct($product);
                    $manager->persist($image); 
                }
            }
            
            $variants = $product->getProductVariants();
            $uniqueVariants = [];
    
            // Combine stocks of variants with the same size
            foreach ($variants as $variant) {
                $size = $variant->getSize();
                if (!isset($uniqueVariants[$size])) {
                    $uniqueVariants[$size] = $variant;
                } else {
                    $existing = $uniqueVariants[$size];
                    $existing->setStock($existing->getStock() + $variant->getStock());
                    $product->removeProductVariant($variant);
                }
            }
    
            foreach ($variants as $variant) {
                $manager->persist($variant);
            }
            
            $product->setName(ucwords($product->getName()));

            foreach ($product->getProductCategories() as $category)
            {
                $category->addProduct($product);
                $manager->persist($product);
            }

            $manager->persist($product);
            $manager->flush();
    
            $this->addFlash(
                'success',
                "Product <strong>{$product->getName()}</strong> has been saved."
            );
    
            return $this->redirectToRoute('product_show', [
                'slug' => $product->getSlug()
            ]);
        }
    
        return $this->render('admin/products/new.html.twig', [
            'myForm' => $form->createView(),
            'colors' => $colors
        ]);
    }

    #[Route('admin/product/{slug}/edit', name: 'product_edit')]
    public function edit(#[MapEntity(mapping: ['slug' => 'slug'])] Product $product, ProductRepository $productRepo, Request $request, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(ProductType::class, $product,[
            'is_edit' => true
        ]);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            if (count($product->getProductVariants()) < 1) {
                $this->addFlash('danger', 'A product must have at least one variant.');
                return $this->redirectToRoute('product_edit', [
                    'slug' => $product->getSlug()
                ]);
            }

            $variants = $product->getProductVariants();
            $uniqueVariants = [];
    
            // Combine stocks of variants with the same size
            foreach ($variants as $variant) {
                $size = $variant->getSize();
                if (!isset($uniqueVariants[$size])) {
                    $uniqueVariants[$size] = $variant;
                } else {
                    $existing = $uniqueVariants[$size];
                    $existing->setStock($existing->getStock() + $variant->getStock());
                    $product->removeProductVariant($variant);
                }
            }
    
            // Clear the existing variants and re-add the unique ones
            $product->getProductVariants()->clear();
            foreach ($uniqueVariants as $variant) {
                $product->addProductVariant($variant);
                $manager->persist($variant);
            }

            $product->setName(ucwords($product->getName()));
            foreach ($product->getProductCategories() as $category)
            {
                $category->addProduct($product);
                $manager->persist($product);
            }
    
            $manager->persist($product);
            $manager->flush();
    
            $this->addFlash(
                'success',
                "La fiche de <strong>".$product->getName()."</strong> a été mise à jour."
            );
    
            return $this->redirectToRoute('product_show', [
                'slug' => $product->getSlug()
            ]);
        }
    
        return $this->render('admin/products/edit.html.twig', [
            'myForm' => $form->createView(),
            'product' => $product
        ]);
    }

    #[Route("admin/product/{slug}/delete", name:"product_delete")]
    public function delete(#[MapEntity(mapping: ['slug' => 'slug'])] Product $product, ProductRepository $productRepo, ProductVariantRepository $productVariantRepo, EntityManagerInterface $manager, OrderItemRepository $orderItemRepository): Response
    {
        $deletedProduct = $productRepo->findOneBy(['name' => 'Deleted Product']);
        $deletedProductVariant = $deletedProduct->getProductVariants()->first();
        
        foreach ($product->getProductVariants() as $variant) {
            $orderItems = $orderItemRepository->findBy(['productVariant' => $variant]);
            foreach ($orderItems as $orderItem) {
                $orderItem->setProductVariant($deletedProductVariant);
                $amountToSubtract = $orderItem->getQuantity() * $variant->getProduct()->getPrice();
                $order = $orderItem->getOrderRelated();
                $newTotal = $order->getTotalPrice() - $amountToSubtract;
                $deliveryPrice = $order->getDelivery()->getPrice();

                if ($newTotal <= $deliveryPrice) {
                    $order->setTotalPrice('0')
                        ->setPaid(true);
                } else {
                    $order->setTotalPrice($newTotal);
                }
                $orderItem->setProductVariant($deletedProductVariant);
                $manager->persist($orderItem);
                $manager->persist($order);
            }
        }
        
        foreach ($product->getProductImages() as $image) {
            if (!empty($image->getFilename())) {
                $imagePath = $this->getParameter('uploads_directory') . '/' . $image->getFilename();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
        
            $manager->remove($image);
        }
        
        $manager->remove($product);
        $manager->flush();
        
        $this->addFlash(
            'success',
            "<strong>".$product->getName()."</strong> deleted successfully !"
        );
        
        return $this->redirectToRoute('products_index');
    }

    #[Route("admin/product/{slug}/pictures", name: "product_pictures")]
    #[IsGranted('ROLE_USER')]
    public function displayPictures(#[MapEntity(mapping: ['slug' => 'slug'])] Product $product): Response
    {
        $images = $product->getProductImages();

        return $this->render("admin/products/display_pictures.html.twig", [
            'product' => $product,
            'images' => $images
        ]);
    }

    #[Route("admin/product/{slug}/add-image", name: "product_add_image")]
    public function addImage(#[MapEntity(mapping: ['slug' => 'slug'])] Product $product, Request $request, EntityManagerInterface $manager): Response
    {
        if (count($product->getProductImages()) >= 5) {
            $this->addFlash('danger', 'Limit of 5 pictures reached. Please delete one before adding a new image.');
            return $this->redirectToRoute('product_pictures', ['slug' => $product->getSlug()]);
        }

        $productImage = new ProductImage();
        $form = $this->createForm(ProductImageType::class, $productImage);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $productImage->getFile();
            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    return $e->getMessage();
                }

                $productImage->setFilename($newFilename);
                $productImage->setProduct($product);
                $manager->persist($productImage);
                $manager->flush();

                $this->addFlash('success', 'New image added successfully!');
                return $this->redirectToRoute('product_pictures', ['slug' => $product->getSlug()]);
            }
        }

        return $this->render('admin/products/add_image.html.twig', [
            'product' => $product,
            'myForm' => $form->createView(),
        ]);
    }

    #[Route("admin/product/picture-delete/{id}", name: "product_picture_delete")]
    #[IsGranted('ROLE_USER')]
    public function deletePicture(#[MapEntity(mapping: ['id' => 'id'])] ProductImage $productImage, EntityManagerInterface $manager): Response
    {
        $product = $productImage->getProduct();

        if (count($product->getProductImages()) <= 1) {
            $this->addFlash('danger', 'A product must have at least one image. Add another image first before deleting this one.');
            return $this->redirectToRoute('product_pictures', [
                'slug' => $product->getSlug(),
            ]);
        }
        
        // Remove the image file if it exists
        if (!empty($productImage->getFilename())) {
            unlink($this->getParameter('uploads_directory') . '/' . $productImage->getFilename());
            $manager->remove($productImage);
        }
        
        $manager->flush();
        
        $this->addFlash('success', 'Picture deleted!');
        
        return $this->redirectToRoute('product_pictures', [
            'slug' => $product->getSlug(),
        ]);
    }
    
}
