<?php

namespace App\Controller;

use App\Repository\RestaurantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class RestaurantController extends AbstractController
{
    #[Route('/restaurant/{id}', name: 'app_restaurant_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, RestaurantRepository $restaurantRepository): Response
    {
        $restaurant = $restaurantRepository->find($id);
        if (null === $restaurant) {
            throw new NotFoundHttpException('Restaurant non trouvé.');
        }

        return $this->render('restaurant/show.html.twig', [
            'restaurant' => $restaurant,
        ]);
    }
}
