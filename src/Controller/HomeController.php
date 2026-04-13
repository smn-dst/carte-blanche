<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\FavoriteRepository;
use App\Repository\RestaurantRepository;
use App\Repository\VendorRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly VendorRequestRepository $vendorRequestRepository,
    ) {
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(
        RestaurantRepository $restaurantRepository,
        FavoriteRepository $favoriteRepository,
        CategoryRepository $categoryRepository,
    ): Response {
        $featuredRestaurants = $restaurantRepository->findFeaturedForHome(5);
        $popularCategories = $categoryRepository->findPopularForHome(4);

        $favoriteRestaurantIds = [];
        $hasPendingVendorRequest = false;

        $user = $this->getUser();

        if ($user instanceof User) {
            $favoriteRestaurantIds = $favoriteRepository->findRestaurantIdsByUser($user);

            if (!$this->isGranted('ROLE_VENDOR')) {
                $hasPendingVendorRequest = $this->vendorRequestRepository->hasPendingRequest($user);
            }
        }

        return $this->render('home/index.html.twig', [
            'featuredRestaurants' => $featuredRestaurants,
            'favoriteRestaurantIds' => $favoriteRestaurantIds,
            'popularCategories' => $popularCategories,
            'hasPendingVendorRequest' => $hasPendingVendorRequest,
        ]);
    }
}
