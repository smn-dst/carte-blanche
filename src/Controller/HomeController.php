<?php

namespace App\Controller;

use App\Repository\RestaurantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(RestaurantRepository $restaurantRepository): Response
    {
        $featuredRestaurants = $restaurantRepository->findFeaturedForHome(5);

        return $this->render('home/index.html.twig', [
            'featuredRestaurants' => $featuredRestaurants,
        ]);
    }
}
