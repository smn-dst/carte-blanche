<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\FavoriteRepository;
use App\Repository\RestaurantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(
        RestaurantRepository $restaurantRepository,
        FavoriteRepository $favoriteRepository,
        CategoryRepository $categoryRepository,
    ): Response {
        $featuredRestaurants = $restaurantRepository->findFeaturedForHome(5);
        $popularCategories = $categoryRepository->findPopularForHome(4);

        $favoriteRestaurantIds = [];
        $user = $this->getUser();
        if ($user instanceof \App\Entity\User) {
            $favoriteRestaurantIds = $favoriteRepository->findRestaurantIdsByUser($user);
        }

        return $this->render('home/index.html.twig', [
            'featuredRestaurants' => $featuredRestaurants,
            'favoriteRestaurantIds' => $favoriteRestaurantIds,
            'popularCategories' => $popularCategories,
        ]);
    }
}
