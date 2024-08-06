<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Product;
use App\Entity\CartItem;
use App\Form\ProductType;
use App\Form\AddToCartType;
use App\Entity\ProductImage;
use App\Entity\ProductVariant;
use App\Form\ProductImageType;
use App\Service\PaginationService;
use App\Repository\ProductRepository;
use App\Repository\CartItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ProductColorRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class ProductController extends AbstractController
{
    #[Route('/store/{page<\d+>?1}', name: 'products_index')]
    public function index(int $page, ProductRepository $productRepo, PaginationService $paginationService, Request $request): Response
    {
        $colorId = $request->query->get('color');
    
        if ($colorId) {
            $products = $productRepo->findByColor($colorId);
        } else {
            $products = $productRepo->findBy([], ['id' => 'DESC']);
        }
    
        $currentPage = $page;
        $itemsPerPage = 9;
    
        $pagination = $paginationService->paginate($products, $currentPage, $itemsPerPage);
        $productsPaginated = $pagination['items'];
        $totalPages = $pagination['totalPages'];
    
        $colors = $productRepo->getColors();
    
        return $this->render('products/index.html.twig', [
            'products' => $productsPaginated,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'colors' => $colors,
            'selectedColor' => $colorId,
        ]);
    }

    #[Route('/product/new', name: 'product_create')]
    public function create(ProductColorRepository $colorRepo, Request $request, EntityManagerInterface $manager): Response
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
                return $this->render("products/new.html.twig", [
                    'myForm' => $form->createView(),
                    'colors' => $colors
                ]);
            }

            foreach ($product->getProductImages() as $image) {
                /** @var UploadedFile $file */
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
    
        return $this->render('products/new.html.twig', [
            'myForm' => $form->createView(),
            'colors' => $colors
        ]);
    }
    

    #[Route('/product/{slug}', name: 'product_show')]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] Product $product,
        ProductRepository $productRepo,
        CartItemRepository $cartItemRepo,
        Request $request,
        EntityManagerInterface $manager
    ): Response {
        $user = $this->getUser();
        $cart = null;
    
        if ($user) {
            $cart = $user->getCart();
            if (!$cart) {
                $cart = new Cart();
                $user->setCart($cart);
                $manager->persist($cart);
                $manager->flush();
            }
        }

        $productVariants = $product->getProductVariants()->toArray();

        // Trier les variantes par taille
        usort($productVariants, function (ProductVariant $a, ProductVariant $b) {
            return $a->getSize() <=> $b->getSize();
        });
    
        $form = $this->createForm(AddToCartType::class, null, [
            'product_variants' => $productVariants,
        ]);
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $productVariant = $data['productVariant'];
            $quantity = $data['quantity'];
    
            // Check stock availability
            if ($quantity > $productVariant->getStock()) {
                $this->addFlash('danger', 'Not enough stock available.');
                return $this->redirectToRoute('product_show', [
                    'slug' => $product->getSlug(),
                ]);
            }
    
            if ($user) {
                // Check if the cart already contains this product variant
                $cartItem = $cartItemRepo->findOneBy([
                    'cart' => $cart,
                    'productVariant' => $productVariant,
                ]);
    
                if ($cartItem) {
                    // If the cart item exists, update the quantity
                    $newQuantity = $cartItem->getQuantity() + $quantity;
    
                    if ($newQuantity > $productVariant->getStock()) {
                        $this->addFlash('danger', 'Not enough stock available for the requested quantity.');
                        return $this->redirectToRoute('product_show', [
                            'slug' => $product->getSlug(),
                        ]);
                    }
    
                    $cartItem->setQuantity($newQuantity);
                } else {
                    $cartItem = new CartItem();
                    $cartItem->setProductVariant($productVariant);
                    $cartItem->setQuantity($quantity);
                    $cartItem->setCart($cart);
    
                    $manager->persist($cartItem);
                }
    
                $manager->flush();
                $this->addFlash('success', 'Item added to cart. <a href="' . $this->generateUrl('cart_show') . '">View Cart</a>');
            }
    
            return $this->redirectToRoute('product_show', [
                'slug' => $product->getSlug(),
            ]);
        }
    
        return $this->render('products/show.html.twig', [
            'product' => $product,
            'myForm' => $form->createView(),
        ]);
    }
    

    #[Route('/product/{slug}/edit', name: 'product_edit')]
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
    
        return $this->render('products/edit.html.twig', [
            'myForm' => $form->createView(),
        ]);
    }
    
    #[Route("/product/{slug}/pictures", name: "product_pictures")]
    #[IsGranted('ROLE_USER')]
    public function displayPictures(#[MapEntity(mapping: ['slug' => 'slug'])] Product $product): Response
    {
        $images = $product->getProductImages();

        return $this->render("products/display_pictures.html.twig", [
            'product' => $product,
            'images' => $images
        ]);
    }

    #[Route("/product/{slug}/add-image", name: "product_add_image")]
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

        return $this->render('products/add_image.html.twig', [
            'product' => $product,
            'myForm' => $form->createView(),
        ]);
    }

    #[Route("product/picture-delete/{id}", name: "product_picture_delete")]
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
