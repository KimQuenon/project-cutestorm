<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Review;
use App\Entity\Product;
use App\Entity\CartItem;
use App\Form\ReviewType;
use App\Form\ProductType;
use App\Form\AddToCartType;
use App\Entity\ProductImage;
use App\Entity\ProductVariant;
use App\Form\ProductImageType;
use App\Service\SearchService;
use App\Service\PaginationService;
use App\Repository\ReviewRepository;
use App\Repository\ProductRepository;
use App\Repository\CartItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ProductColorRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class ProductController extends AbstractController
{
    private $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    
    #[Route('/store/{page<\d+>?1}', name: 'store')]
    public function index(int $page, ProductRepository $productRepo, PaginationService $paginationService, Request $request): Response
    {
        $colorId = $request->query->get('color');
        $categoryId = $request->query->get('category');
        $sort = $request->query->get('sort');
    
        $orderBy = [];
        switch ($sort) {
            case 'name_asc':
                $orderBy = ['name' => 'ASC'];
                break;
            case 'name_desc':
                $orderBy = ['name' => 'DESC'];
                break;
            case 'date_asc':
                $orderBy = ['id' => 'ASC'];
                break;
            case 'date_desc':
                $orderBy = ['id' => 'DESC'];
                break;
            default:
                $orderBy = ['id' => 'DESC'];
                break;
        }
    
        if ($colorId && $categoryId) {
            $products = $productRepo->findByColorAndCategory($colorId, $categoryId, $orderBy);
        } elseif ($colorId) {
            $products = $productRepo->findByColor($colorId, $orderBy);
        } elseif ($categoryId) {
            $products = $productRepo->findByCategory($categoryId, $orderBy);
        } else {
            $products = $productRepo->findBy([], $orderBy);
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
            'sort' => $sort // Passe le paramètre de tri au template
        ]);
    }
    
    #[Route('/store/search/ajax', name: 'store_search_ajax', methods: ['GET'])]
    public function searchAjax(Request $request): JsonResponse
    {
        $query = $request->query->get('query', '');

        if (empty($query)) {
            return new JsonResponse([]);
        }

        $results = $this->searchService->search($query, 'product');

        return new JsonResponse($results);
    }

    #[Route('/product/{slug}', name: 'product_show')]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] Product $product,
        ProductRepository $productRepo,
        CartItemRepository $cartItemRepo,
        ReviewRepository $reviewRepo,
        Request $request,
        EntityManagerInterface $manager
    ): Response {
        $user = $this->getUser();
        $categories = $product->getProductCategories();
        $reviews = $product->getReviews();

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


        /*---------------------------------REVIEW-----------------------------------*/

        $averageRating = $reviewRepo->getAverageRating($product->getId());

        $existingReview = null;
        $productBought = false;

        if ($user) {
            $existingReview = $reviewRepo->findOneBy([
                'author' => $user,
                'product' => $product,
            ]);
    
            $productBought = $reviewRepo->hasUserBoughtProduct($user, $product);
        }

        $review = new Review();
        $reviewForm = $this->createform(ReviewType::class, $review);
        $reviewForm->handleRequest($request);

        if($reviewForm->isSubmitted() && $reviewForm->IsValid())
        {
            $review->setAuthor($user);
            $review->setProduct($product);

            $manager->persist($review);    
            $manager->flush();

            $this->addFlash(
                'success',
                "Thank you for this feedback !"
            );

            return $this->redirectToRoute('product_show', [
                'slug' => $product->getSlug(),
            ]);
        }
    


        $recentProducts = array_slice(array_filter($productRepo->findBy([], ['id' => 'DESC']), fn($p) => $p !== $product), 0, 3);


        return $this->render('products/show.html.twig', [
            'product' => $product,
            'categories' => $categories,
            'myForm' => $form->createView(),
            'reviews' => $reviews,
            'reviewForm' => $reviewForm->createView(),
            'existingReview'=> $existingReview,
            'averageRating' => $averageRating,
            'productBought' => $productBought,
            'recentProducts' => $recentProducts
        ]);
    }
}
