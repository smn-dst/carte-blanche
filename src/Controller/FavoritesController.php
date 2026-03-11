<?php

namespace App\Controller;

use App\Entity\Favorite;
use App\Repository\FavoriteRepository;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FavoritesController extends AbstractController
{
    #[Route('/favoris', name: 'app_favorites', methods: ['GET'])]
    public function index(FavoriteRepository $favoriteRepository, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $sort = $request->query->get('sort', 'date');
        if (!\in_array($sort, ['date', 'price_asc', 'price_desc'], true)) {
            $sort = 'date';
        }

        $favorites = $favoriteRepository->findByUser($user, $sort);
        $restaurants = array_map(fn (Favorite $f) => $f->getRestaurant(), $favorites);

        return $this->render('favorites/index.html.twig', [
            'restaurants' => $restaurants,
            'sort' => $sort,
        ]);
    }

    #[Route('/restaurant/{id}/favoris', name: 'app_favorite_add', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function add(
        int $id,
        Request $request,
        RestaurantRepository $restaurantRepository,
        FavoriteRepository $favoriteRepository,
        EntityManagerInterface $em,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('favorite_add_'.$id, \is_string($token) ? $token : null)) {
            return $this->redirectToRoute('app_restaurant_show', ['id' => $id]);
        }

        $restaurant = $restaurantRepository->find($id);
        if (null === $restaurant) {
            return $this->redirectToRoute('app_encheres');
        }

        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }
        $existing = $favoriteRepository->findOneBy(['user' => $user, 'restaurant' => $restaurant]);
        if (null !== $existing) {
            return $this->redirectToRoute('app_restaurant_show', ['id' => $id]);
        }

        $favorite = new Favorite();
        $favorite->setUser($user);
        $favorite->setRestaurant($restaurant);
        $now = new \DateTimeImmutable();
        $favorite->setCreatedAt($now);
        $favorite->setUpdatedAt($now);

        $em->persist($favorite);
        $restaurant->setFavoriteCount($restaurant->getFavoriteCount() + 1);
        $em->flush();

        $referer = $request->headers->get('Referer');

        return $referer
            ? $this->redirect($referer)
            : $this->redirectToRoute('app_restaurant_show', ['id' => $id]);
    }

    #[Route('/restaurant/{id}/favoris/retirer', name: 'app_favorite_remove', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function remove(
        int $id,
        Request $request,
        RestaurantRepository $restaurantRepository,
        FavoriteRepository $favoriteRepository,
        EntityManagerInterface $em,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('favorite_remove_'.$id, \is_string($token) ? $token : null)) {
            return $this->redirectToRoute('app_restaurant_show', ['id' => $id]);
        }

        $restaurant = $restaurantRepository->find($id);
        if (null === $restaurant) {
            return $this->redirectToRoute('app_encheres');
        }

        $user = $this->getUser();
        $favorite = $favoriteRepository->findOneBy(['user' => $user, 'restaurant' => $restaurant]);
        if (null === $favorite) {
            return $this->redirectToRoute('app_restaurant_show', ['id' => $id]);
        }

        $em->remove($favorite);
        $restaurant->setFavoriteCount(max(0, $restaurant->getFavoriteCount() - 1));
        $em->flush();

        $referer = $request->headers->get('Referer');

        return $referer
            ? $this->redirect($referer)
            : $this->redirectToRoute('app_restaurant_show', ['id' => $id]);
    }
}
