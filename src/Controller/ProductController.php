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
    #[Route('/store/{page<\d+>?1}', name: 'store')]
    public function index(int $page, ProductRepository $productRepo, PaginationService $paginationService, Request $request): Response
    {
        $colorId = $request->query->get('color');
        $categoryId = $request->query->get('category');
    
        if ($colorId && $categoryId) {
            $products = $productRepo->findByColorAndCategory($colorId, $categoryId);
        } elseif ($colorId) {
            $products = $productRepo->findByColor($colorId);
        } elseif ($categoryId) {
            $products = $productRepo->findByCategory($categoryId);
        } else {
            $products = $productRepo->findBy([], ['id' => 'DESC']);
        }
    
        $currentPage = $page;
        $itemsPerPage = 9;
    
        $pagination = $paginationService->paginate($products, $currentPage, $itemsPerPage);
        $productsPaginated = $pagination['items'];
        $totalPages = $pagination['totalPages'];
    
        $colors = $productRepo->getColors();
        $categories = $productRepo->getCategories();
    
        return $this->render('products/index.html.twig', [
            'products' => $productsPaginated,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'colors' => $colors,
            'selectedColor' => $colorId,
            'categories' => $categories,
            'selectedCategory' => $categoryId,
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
        $categories = $product->getProductCategories();

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
            'categories' => $categories,
            'myForm' => $form->createView(),
        ]);
    }
    
    
    // #[Route("/product/{slug}/pictures", name: "product_pictures")]
    // #[IsGranted('ROLE_USER')]
    // public function displayPictures(#[MapEntity(mapping: ['slug' => 'slug'])] Product $product): Response
    // {
    //     $images = $product->getProductImages();

    //     return $this->render("products/display_pictures.html.twig", [
    //         'product' => $product,
    //         'images' => $images
    //     ]);
    // }

    // #[Route("/product/{slug}/add-image", name: "product_add_image")]
    // public function addImage(#[MapEntity(mapping: ['slug' => 'slug'])] Product $product, Request $request, EntityManagerInterface $manager): Response
    // {
    //     if (count($product->getProductImages()) >= 5) {
    //         $this->addFlash('danger', 'Limit of 5 pictures reached. Please delete one before adding a new image.');
    //         return $this->redirectToRoute('product_pictures', ['slug' => $product->getSlug()]);
    //     }

    //     $productImage = new ProductImage();
    //     $form = $this->createForm(ProductImageType::class, $productImage);

    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $file = $productImage->getFile();
    //         if ($file) {
    //             $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    //             $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
    //             $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

    //             try {
    //                 $file->move(
    //                     $this->getParameter('uploads_directory'),
    //                     $newFilename
    //                 );
    //             } catch (FileException $e) {
    //                 return $e->getMessage();
    //             }

    //             $productImage->setFilename($newFilename);
    //             $productImage->setProduct($product);
    //             $manager->persist($productImage);
    //             $manager->flush();

    //             $this->addFlash('success', 'New image added successfully!');
    //             return $this->redirectToRoute('product_pictures', ['slug' => $product->getSlug()]);
    //         }
    //     }

    //     return $this->render('products/add_image.html.twig', [
    //         'product' => $product,
    //         'myForm' => $form->createView(),
    //     ]);
    // }

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
