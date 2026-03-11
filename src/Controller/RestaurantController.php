<?php

namespace App\Controller;

use App\Dto\RestaurantInputDto;
use App\Entity\User;
use App\Exception\RestaurantNotFoundException;
use App\Form\RestaurantFormType;
use App\Repository\FavoriteRepository;
use App\Repository\RestaurantRepository;
use App\Security\Voter\RestaurantVoter;
use App\Service\AiDescriptionService;
use App\Service\RestaurantService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RestaurantController extends AbstractController
{
    public function __construct(
        private readonly RestaurantService $restaurantService,
    ) {
    }

    #[Route('/restaurant/{id}', name: 'app_restaurant_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, RestaurantRepository $restaurantRepository, FavoriteRepository $favoriteRepository): Response
    {
        $restaurant = $restaurantRepository->find($id);
        if (null === $restaurant) {
            throw $this->createNotFoundException('Restaurant non trouvé.');
        }

        $isFavorite = false;
        if ($this->getUser()) {
            $isFavorite = null !== $favoriteRepository->findOneBy([
                'user' => $this->getUser(),
                'restaurant' => $restaurant,
            ]);
        }

        return $this->render('restaurant/show.html.twig', [
            'restaurant' => $restaurant,
            'isFavorite' => $isFavorite,
        ]);
    }

    #[Route('/mes-restaurants', name: 'app_restaurant_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!in_array('ROLE_VENDOR', $user->getRoles(), true)) {
            return $this->redirect('/');
        }

        return $this->render('restaurant/index.html.twig', [
            'restaurants' => $this->restaurantService->findByOwner($user),
        ]);
    }

    #[Route('/restaurant/nouveau', name: 'app_restaurant_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $dto = new RestaurantInputDto();
        $form = $this->createForm(RestaurantFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $restaurant = $this->restaurantService->create($user, $dto);
            $this->addFlash('success', 'Restaurant créé avec succès.');

            return $this->redirectToRoute('app_restaurant_edit', ['id' => $restaurant->getId()]);
        }

        return $this->render('restaurant/new.html.twig', [
            'form' => $form,
            'googleMapsApiKey' => $this->resolveGoogleMapsApiKey(),
        ]);
    }

    #[Route('/restaurant/{id}/modifier', name: 'app_restaurant_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        try {
            $restaurant = $this->restaurantService->findOrFail($id);
        } catch (RestaurantNotFoundException) {
            throw $this->createNotFoundException('Restaurant non trouvé.');
        }

        $this->denyAccessUnlessGranted(RestaurantVoter::EDIT, $restaurant);

        $dto = $this->restaurantService->buildInputDto($restaurant);
        $form = $this->createForm(RestaurantFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->restaurantService->update($restaurant, $dto);
            $this->addFlash('success', 'Restaurant mis à jour avec succès.');

            return $this->redirectToRoute('app_restaurant_edit', ['id' => $id]);
        }

        return $this->render('restaurant/edit.html.twig', [
            'form' => $form,
            'restaurant' => $restaurant,
            'googleMapsApiKey' => $this->resolveGoogleMapsApiKey(),
        ]);
    }

    #[Route('/restaurant/{id}/supprimer', name: 'app_restaurant_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        try {
            $restaurant = $this->restaurantService->findOrFail($id);
        } catch (RestaurantNotFoundException) {
            throw $this->createNotFoundException('Restaurant non trouvé.');
        }

        $this->denyAccessUnlessGranted(RestaurantVoter::DELETE, $restaurant);

        $csrfToken = (string) ($request->request->get('_token') ?? $request->getPayload()->getString('_token'));
        if (!$this->isCsrfTokenValid('delete_restaurant_'.$id, $csrfToken)) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_restaurant_index');
        }

        $this->restaurantService->delete($restaurant);
        $this->addFlash('success', 'Restaurant supprimé.');

        return $this->redirectToRoute('app_restaurant_index');
    }

    #[Route('/restaurant/{restaurantId}/image/{imageId}/supprimer', name: 'app_restaurant_image_delete', requirements: ['restaurantId' => '\d+', 'imageId' => '\d+'], methods: ['POST'])]
    public function deleteImage(int $restaurantId, int $imageId, Request $request): Response
    {
        try {
            $restaurant = $this->restaurantService->findOrFail($restaurantId);
        } catch (RestaurantNotFoundException) {
            throw $this->createNotFoundException('Restaurant non trouvé.');
        }

        $this->denyAccessUnlessGranted(RestaurantVoter::EDIT, $restaurant);

        $csrfToken = (string) ($request->request->get('_token') ?? $request->getPayload()->getString('_token'));
        if (!$this->isCsrfTokenValid('delete_image_'.$imageId, $csrfToken)) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_restaurant_edit', ['id' => $restaurantId]);
        }

        $this->restaurantService->deleteImage($restaurantId, $imageId);
        $this->addFlash('success', 'Image supprimée.');

        return $this->redirectToRoute('app_restaurant_edit', ['id' => $restaurantId]);
    }

    private function resolveGoogleMapsApiKey(): string
    {
        $apiKey = $_SERVER['GOOGLE_MAPS_API_KEY'] ?? $_ENV['GOOGLE_MAPS_API_KEY'] ?? null;
        if (!is_string($apiKey)) {
            return '';
        }

        return trim($apiKey);
    }

    #[Route('/restaurant/generate-description', name: 'app_restaurant_generate_description', methods: ['POST'])]
    public function generateDescription(
        Request $request,
        AiDescriptionService $aiDescriptionService,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données invalides'], 400);
        }

        try {
            $description = $aiDescriptionService->generateDescription($data);

            return $this->json(['description' => $description]);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Erreur de génération : '.$e->getMessage()], 500);
        }
    }
}
