<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\FavoriteRepository;
use App\Repository\RestaurantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EncheresController extends AbstractController
{
    #[Route('/encheres', name: 'app_encheres', methods: ['GET'])]
    public function index(Request $request, RestaurantRepository $restaurantRepository, CategoryRepository $categoryRepository, FavoriteRepository $favoriteRepository): Response
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

        $revenueSort = $request->query->get('revenueSort', '');

        // 1. Filtre : données pour les filtres (catégories)
        $categories = $categoryRepository->findAll();

        $perPage = 25;
        $page = max(1, (int) $request->query->get('page', 1));

        // 2. Pagination : total + fourchette de prix (une requête)
        $paginationData = $restaurantRepository->getPaginationAndPriceRange(
            $search ?: null,
            $categoryId ?: null,
            $minPrice,
            $maxPrice
        );
        $priceRange = ['min' => $paginationData['minPrice'], 'max' => $paginationData['maxPrice']];
        $currentMinPrice = $minPrice ?? $priceRange['min'];
        $currentMaxPrice = $maxPrice ?? $priceRange['max'];

        $totalRestaurants = $paginationData['total'];
        $totalPages = (int) ceil($totalRestaurants / $perPage) ?: 1;
        if ($page > $totalPages) {
            $request->query->set('page', '1');

            return $this->redirectToRoute('app_encheres', $request->query->all());
        }

        $offset = ($page - 1) * $perPage;

        // 3. Affichage : restaurants de la page (une requête)
        $restaurants = $restaurantRepository->searchByNameAndCategory(
            $search ?: null,
            $categoryId ?: null,
            $sortBy ?: null,
            $minPrice,
            $maxPrice,
            $priceSort ?: null,
            $revenueSort ?: null,
            $perPage,
            $offset
        );

        $favoriteRestaurantIds = [];
        $user = $this->getUser();
        if ($user instanceof \App\Entity\User) {
            $favoriteRestaurantIds = $favoriteRepository->findRestaurantIdsByUser($user);
        }

        return $this->render('encheres/index.html.twig', [
            'restaurants' => $restaurants,
            'favoriteRestaurantIds' => $favoriteRestaurantIds,
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
            'revenueSort' => $revenueSort,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_count' => $totalRestaurants,
                'per_page' => $perPage,
            ],
        ]);
    }

    #[Route('/encheres/{id}', name: 'app_enchere_detail', methods: ['GET'])]
    public function detail(int $id, RestaurantRepository $restaurantRepository, FavoriteRepository $favoriteRepository): Response
    {
        $restaurant = $restaurantRepository->find($id);

        if (!$restaurant) {
            return $this->render('encheres/404.html.twig');
        }

        $isFavorite = false;
        $user = $this->getUser();
        if ($user instanceof \App\Entity\User) {
            $isFavorite = null !== $favoriteRepository->findOneBy([
                'user' => $user,
                'restaurant' => $restaurant,
            ]);
        }

        return $this->render('encheres/detail.html.twig', [
            'restaurant' => $restaurant,
            'isFavorite' => $isFavorite,
        ]);
    }
}
