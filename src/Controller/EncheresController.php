<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\RestaurantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EncheresController extends AbstractController
{
    #[Route('/encheres', name: 'app_encheres', methods: ['GET'])]
    public function index(Request $request, RestaurantRepository $restaurantRepository, CategoryRepository $categoryRepository): Response
    {
        $search = $request->query->get('search', '');
        $categoryParam = $request->query->get('category', '');
        $categoryId = '' !== $categoryParam ? (int) $categoryParam : 0;
        $sortBy = $request->query->get('sort', '');
        $priceSort = $request->query->get('priceSort', '');

        $minPriceParam = $request->query->get('minPrice', '');
        $maxPriceParam = $request->query->get('maxPrice', '');
        $minPrice = '' !== $minPriceParam ? (float) $minPriceParam : null;
        $maxPrice = '' !== $maxPriceParam ? (float) $maxPriceParam : null;

        $categories = $categoryRepository->findAll();
        $priceRange = $restaurantRepository->getPriceRange();

        // Utilise les valeurs par défaut si aucun filtre de prix n'est défini
        $currentMinPrice = $minPrice ?? $priceRange['min'];
        $currentMaxPrice = $maxPrice ?? $priceRange['max'];

        if ($search || $categoryId || $sortBy || null !== $minPrice || null !== $maxPrice || $priceSort) {
            $restaurants = $restaurantRepository->searchByNameAndCategory(
                $search,
                $categoryId ?: null,
                $sortBy ?: null,
                $minPrice,
                $maxPrice,
                $priceSort ?: null
            );
        } else {
            $restaurants = $restaurantRepository->findAll();
        }

        return $this->render('encheres/index.html.twig', [
            'restaurants' => $restaurants,
            'categories' => $categories,
            'search' => $search,
            'categoryId' => $categoryId,
            'sortBy' => $sortBy,
            'priceSort' => $priceSort,
            'priceRange' => $priceRange,
            'currentMinPrice' => $currentMinPrice,
            'currentMaxPrice' => $currentMaxPrice,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
        ]);
    }
}
