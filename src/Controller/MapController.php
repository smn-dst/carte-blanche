<?php

namespace App\Controller;

use App\Repository\RestaurantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MapController extends AbstractController
{
    #[Route('/carte', name: 'app_map', methods: ['GET'])]
    public function index(RestaurantRepository $restaurantRepository): Response
    {
        $restaurants = $restaurantRepository->findForMap();

        return $this->render('map/index.html.twig', [
            'mapboxToken' => $this->getParameter('mapbox_token'),
            'restaurants' => $restaurants,
        ]);
    }

    #[Route('/api/map/restaurants', name: 'app_api_map_restaurants', methods: ['GET'])]
    public function apiRestaurants(RestaurantRepository $restaurantRepository): JsonResponse
    {
        $restaurants = $restaurantRepository->findForMap();

        $geojson = [
            'type' => 'FeatureCollection',
            'features' => array_map(fn ($r) => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$r->getLongitude(), $r->getLatitude()],
                ],
                'properties' => [
                    'id' => $r->getId(),
                    'name' => $r->getName(),
                    'address' => $r->getAddress(),
                    'askingPrice' => $r->getAskingPrice(),
                    'capacity' => $r->getCapacity(),
                    'category' => ($firstCategory = $r->getCategories()->first()) instanceof \App\Entity\Category ? $firstCategory->getName() : null,
                    'image' => (($firstImage = $r->getFirstImage()) !== null) ? '/uploads/restaurants/'.$firstImage->getFileName() : null,
                    'url' => $this->generateUrl('app_restaurant_show', ['id' => $r->getId()]),
                    'status' => $r->getStatus()->value,
                ],
            ], $restaurants),
        ];

        return $this->json($geojson);
    }
}
