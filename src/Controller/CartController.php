<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CartItemRepository;
use App\Repository\RestaurantRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
    ) {
    }

    #[Route('/panier', name: 'app_cart', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $cart = $this->cartService->getOrCreateCart($user);

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'total' => $this->cartService->getCartTotal($cart),
            'itemCount' => $this->cartService->getItemCount($cart),
        ]);
    }

    #[Route('/panier/ajouter/{id}', name: 'app_cart_add', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function add(int $id, Request $request, RestaurantRepository $restaurantRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('cart_add_'.$id, \is_string($token) ? $token : null)) {
            return $this->redirectToRoute('app_enchere_detail', ['id' => $id]);
        }

        $restaurant = $restaurantRepository->find($id);
        if (null === $restaurant) {
            throw $this->createNotFoundException('Restaurant non trouvé.');
        }

        $remaining = $restaurant->getMaxCapacity() - $restaurant->getTicketsSold();
        if ($remaining <= 0) {
            $this->addFlash('error', 'Cette enchère est complète.');

            return $this->redirectToRoute('app_enchere_detail', ['id' => $id]);
        }

        $quantity = max(1, (int) $request->request->get('quantity', 1));

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $this->cartService->addItem($user, $restaurant, $quantity);
        $this->addFlash('success', $quantity.' place(s) ajoutée(s) au panier.');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/panier/modifier/{id}', name: 'app_cart_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function update(int $id, Request $request, CartItemRepository $cartItemRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('cart_update_'.$id, \is_string($token) ? $token : null)) {
            return $this->redirectToRoute('app_cart');
        }

        $item = $cartItemRepository->find($id);
        if (null === $item) {
            throw $this->createNotFoundException('Article non trouvé.');
        }

        // Vérifier que l'item appartient bien à l'utilisateur
        $user = $this->getUser();
        if (!$user instanceof User || $item->getCart()?->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $quantity = (int) $request->request->get('quantity', 1);
        $this->cartService->updateQuantity($item, $quantity);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/panier/supprimer/{id}', name: 'app_cart_remove', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function remove(int $id, Request $request, CartItemRepository $cartItemRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('cart_remove_'.$id, \is_string($token) ? $token : null)) {
            return $this->redirectToRoute('app_cart');
        }

        $item = $cartItemRepository->find($id);
        if (null === $item) {
            throw $this->createNotFoundException('Article non trouvé.');
        }

        $user = $this->getUser();
        if (!$user instanceof User || $item->getCart()?->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $this->cartService->removeItem($item);
        $this->addFlash('success', 'Article retiré du panier.');

        return $this->redirectToRoute('app_cart');
    }
}
