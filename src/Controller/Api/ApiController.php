<?php

namespace App\Controller\Api;

use App\Entity\Favorite;
use App\Entity\Restaurant;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\FavoriteRepository;
use App\Repository\OrderRepository;
use App\Repository\RestaurantRepository;
use App\Service\ChatbotService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api', name: 'api_public_')]
#[OA\Tag(name: 'Carte Blanche API')]
class ApiController extends AbstractController
{
    // ── 1. GET /api/restaurants ──────────────────────────────────────────

    #[Route('/restaurants', name: 'restaurants', methods: ['GET'])]
    #[OA\Get(
        path: '/api/restaurants',
        operationId: 'getRestaurants',
        summary: 'Liste paginée des restaurants',
        description: 'Restaurants publiés avec filtres optionnels.',
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'minPrice', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'maxPrice', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [new OA\Response(response: 200, description: 'Succès')]
    )]
    public function restaurants(Request $request, RestaurantRepository $repo): JsonResponse
    {
        $search = $request->query->getString('search') ?: null;
        $categoryId = $request->query->getInt('category') ?: null;
        $minPrice = $request->query->has('minPrice') ? (float) $request->query->getString('minPrice') : null;
        $maxPrice = $request->query->has('maxPrice') ? (float) $request->query->getString('maxPrice') : null;
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(50, max(1, $request->query->getInt('limit', 10)));
        $offset = ($page - 1) * $limit;

        $restaurants = $repo->searchByNameAndCategory($search, $categoryId, null, $minPrice, $maxPrice, null, null, $limit, $offset);
        $total = $repo->countSearchByNameAndCategory($search, $categoryId, $minPrice, $maxPrice);

        return $this->json([
            'data' => array_map([$this, 'serializeRestaurant'], $restaurants),
            'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'total_pages' => (int) ceil($total / max($limit, 1))],
        ]);
    }

    // ── 2. GET /api/restaurants/map ──────────────────────────────────────

    #[Route('/restaurants/map', name: 'restaurants_map', methods: ['GET'])]
    #[OA\Get(
        path: '/api/restaurants/map',
        operationId: 'getRestaurantsMap',
        summary: 'GeoJSON pour la carte Mapbox',
        responses: [new OA\Response(response: 200, description: 'GeoJSON FeatureCollection')]
    )]
    public function map(RestaurantRepository $repo): JsonResponse
    {
        $features = [];
        foreach ($repo->searchByNameAndCategory(null, null, null, null, null, null, null, 500, 0) as $r) {
            if (null === $r->getLatitude() || null === $r->getLongitude()) {
                continue;
            }
            $img = $r->getFirstImage();
            $features[] = [
                'type' => 'Feature',
                'geometry' => ['type' => 'Point', 'coordinates' => [(float) $r->getLongitude(), (float) $r->getLatitude()]],
                'properties' => [
                    'id' => $r->getId(),
                    'name' => $r->getName(),
                    'address' => $r->getAddress(),
                    'askingPrice' => (float) $r->getAskingPrice(),
                    'category' => $r->getCategories()->first() ? $r->getCategories()->first()->getName() : null,
                    'image' => $img ? '/uploads/restaurants/'.$img->getFileName() : null,
                    'url' => $this->generateUrl('app_restaurant_show', ['id' => $r->getId()]),
                ],
            ];
        }

        return $this->json(['type' => 'FeatureCollection', 'features' => $features]);
    }

    // ── 3. GET /api/restaurants/{id} ─────────────────────────────────────

    #[Route('/restaurants/{id}', name: 'restaurant', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/restaurants/{id}',
        operationId: 'getRestaurant',
        summary: 'Détail complet d\'un restaurant',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Succès'),
            new OA\Response(response: 404, description: 'Non trouvé'),
        ]
    )]
    public function restaurant(int $id, RestaurantRepository $repo): JsonResponse
    {
        $r = $repo->find($id);

        return $r
            ? $this->json($this->serializeRestaurant($r, true))
            : $this->json(['error' => 'Restaurant non trouvé.'], 404);
    }

    // ── 4. GET /api/categories ───────────────────────────────────────────

    #[Route('/categories', name: 'categories', methods: ['GET'])]
    #[OA\Get(
        path: '/api/categories',
        operationId: 'getCategories',
        summary: 'Liste des catégories de cuisine',
        responses: [new OA\Response(response: 200, description: 'Succès')]
    )]
    public function categories(CategoryRepository $repo): JsonResponse
    {
        return $this->json(array_map(static fn ($c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'slug' => $c->getSlug(),
        ], $repo->findAll()));
    }

    // ── 5. GET /api/encheres ─────────────────────────────────────────────

    #[Route('/encheres', name: 'encheres', methods: ['GET'])]
    #[OA\Get(
        path: '/api/encheres',
        operationId: 'getEncheres',
        summary: 'Enchères actives paginées',
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [new OA\Response(response: 200, description: 'Succès')]
    )]
    public function encheres(Request $request, RestaurantRepository $repo): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(50, max(1, $request->query->getInt('limit', 10)));
        $total = $repo->countSearchByNameAndCategory(null, null);

        return $this->json([
            'data' => array_map([$this, 'serializeRestaurant'], $repo->searchByNameAndCategory(null, null, null, null, null, null, null, $limit, ($page - 1) * $limit)),
            'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'total_pages' => (int) ceil($total / max($limit, 1))],
        ]);
    }

    // ── 6. GET /api/me  🔒 ───────────────────────────────────────────────

    #[Route('/me', name: 'me', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(
        path: '/api/me',
        operationId: 'getMe',
        summary: 'Profil de l\'utilisateur connecté',
        security: [['cookieAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Succès'), new OA\Response(response: 401, description: 'Non authentifié')]
    )]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
            'isVendor' => in_array('ROLE_VENDOR', $user->getRoles(), true),
            'createdAt' => $user->getCreatedAt()?->format('Y-m-d'),
        ]);
    }

    // ── 7. GET /api/me/orders  🔒 ────────────────────────────────────────

    #[Route('/me/orders', name: 'me_orders', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(
        path: '/api/me/orders',
        operationId: 'getMyOrders',
        summary: 'Mes commandes',
        security: [['cookieAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Succès')]
    )]
    public function myOrders(OrderRepository $repo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json(array_map(static fn ($o) => [
            'id' => $o->getId(),
            'reference' => $o->getReference(),
            'totalAmount' => (float) $o->getTotalAmount(),
            'status' => $o->getStatus()->value,
            'ticketCount' => $o->getTickets()->count(),
            'createdAt' => $o->getCreatedAt()?->format('Y-m-d H:i'),
        ], $repo->findBy(['buyer' => $user], ['createdAt' => 'DESC'])));
    }

    // ── 8. GET /api/me/tickets  🔒 ───────────────────────────────────────

    #[Route('/me/tickets', name: 'me_tickets', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(
        path: '/api/me/tickets',
        operationId: 'getMyTickets',
        summary: 'Mes tickets (avec QR codes)',
        security: [['cookieAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Succès')]
    )]
    public function myTickets(OrderRepository $repo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $tickets = [];

        foreach ($repo->findBy(['buyer' => $user]) as $order) {
            foreach ($order->getTickets() as $ticket) {
                $tickets[] = [
                    'id' => $ticket->getId(),
                    'qrCode' => $ticket->getQrCode(),
                    'status' => $ticket->getStatus()->value,
                    'restaurantName' => $ticket->getRestaurant()?->getName(),
                    'auctionDate' => $ticket->getRestaurant()?->getAuctionDate()?->format('Y-m-d'),
                    'auctionTime' => $ticket->getRestaurant()?->getAuctionTime()?->format('H:i'),
                    'orderRef' => $order->getReference(),
                    'createdAt' => $ticket->getCreatedAt()?->format('Y-m-d H:i'),
                ];
            }
        }

        return $this->json($tickets);
    }

    // ── 9. GET /api/me/favorites  🔒 ─────────────────────────────────────

    #[Route('/me/favorites', name: 'me_favorites', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Get(
        path: '/api/me/favorites',
        operationId: 'getMyFavorites',
        summary: 'Mes favoris',
        security: [['cookieAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Succès')]
    )]
    public function myFavorites(FavoriteRepository $repo): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $list = [];
        foreach ($repo->findBy(['user' => $user]) as $f) {
            $restaurant = $f->getRestaurant();
            if (!$restaurant instanceof Restaurant) {
                continue;
            }
            $list[] = $this->serializeRestaurant($restaurant);
        }

        return $this->json($list);
    }

    // ── 10. POST /api/me/favorites/{id}  🔒 ──────────────────────────────

    #[Route('/me/favorites/{id}', name: 'me_favorites_add', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Post(
        path: '/api/me/favorites/{id}',
        operationId: 'addFavorite',
        summary: 'Ajouter un favori',
        security: [['cookieAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 201, description: 'Ajouté'),
            new OA\Response(response: 404, description: 'Restaurant non trouvé'),
            new OA\Response(response: 409, description: 'Déjà en favoris'),
        ]
    )]
    public function addFavorite(int $id, RestaurantRepository $restaurantRepo, FavoriteRepository $favRepo, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $restaurant = $restaurantRepo->find($id);

        if (!$restaurant) {
            return $this->json(['error' => 'Restaurant non trouvé.'], 404);
        }
        if ($favRepo->findOneBy(['user' => $user, 'restaurant' => $restaurant])) {
            return $this->json(['error' => 'Déjà en favoris.'], 409);
        }

        $now = new \DateTimeImmutable();
        $f = new Favorite();
        $f->setUser($user)->setRestaurant($restaurant)->setCreatedAt($now)->setUpdatedAt($now);
        $em->persist($f);
        $em->flush();

        return $this->json(['message' => 'Ajouté aux favoris.'], 201);
    }

    // ── 11. DELETE /api/me/favorites/{id}  🔒 ────────────────────────────

    #[Route('/me/favorites/{id}', name: 'me_favorites_remove', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Delete(
        path: '/api/me/favorites/{id}',
        operationId: 'removeFavorite',
        summary: 'Retirer un favori',
        security: [['cookieAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Retiré'),
            new OA\Response(response: 404, description: 'Non trouvé'),
        ]
    )]
    public function removeFavorite(int $id, RestaurantRepository $restaurantRepo, FavoriteRepository $favRepo, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $r = $restaurantRepo->find($id);
        $favorite = $r ? $favRepo->findOneBy(['user' => $user, 'restaurant' => $r]) : null;

        if (!$favorite) {
            return $this->json(['error' => 'Favori non trouvé.'], 404);
        }

        $em->remove($favorite);
        $em->flush();

        return $this->json(['message' => 'Retiré des favoris.']);
    }

    // ── 12. POST /api/chatbot ────────────────────────────────────────────

    #[Route('/chatbot', name: 'chatbot', methods: ['POST'])]
    #[OA\Post(
        path: '/api/chatbot',
        operationId: 'askChatbot',
        summary: 'Chatbot IA Carte Blanche',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['question'],
                properties: [
                    new OA\Property(property: 'question', type: 'string', example: 'Comment acheter un ticket ?'),
                    new OA\Property(property: 'history', type: 'array', items: new OA\Items(type: 'object')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Réponse'),
            new OA\Response(response: 400, description: 'Question invalide'),
        ]
    )]
    public function chatbot(Request $request, ChatbotService $chatbotService): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $question = trim((string) ($data['question'] ?? ''));
        $history = (array) ($data['history'] ?? []);

        if ('' === $question) {
            return $this->json(['error' => 'Question vide.'], 400);
        }
        if (mb_strlen($question) > 500) {
            return $this->json(['error' => 'Question trop longue.'], 400);
        }

        $user = $this->getUser();

        return $this->json([
            'response' => $chatbotService->ask($question, $history, $user instanceof User ? $user : null),
        ]);
    }

    // ── Sérialiseur ──────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function serializeRestaurant(Restaurant $r, bool $full = false): array
    {
        $img = $r->getFirstImage();
        $data = [
            'id' => $r->getId(),
            'name' => $r->getName(),
            'address' => $r->getAddress(),
            'latitude' => $r->getLatitude(),
            'longitude' => $r->getLongitude(),
            'askingPrice' => null !== $r->getAskingPrice() ? (float) $r->getAskingPrice() : null,
            'ticketPrice' => null !== $r->getTicketPrice() ? (float) $r->getTicketPrice() : null,
            'maxCapacity' => $r->getMaxCapacity(),
            'capacity' => $r->getCapacity(),
            'status' => $r->getStatus()->value,
            'auctionDate' => $r->getAuctionDate()?->format('Y-m-d'),
            'auctionTime' => $r->getAuctionTime()?->format('H:i'),
            'auctionLocation' => $r->getAuctionLocation(),
            'categories' => $r->getCategories()->map(fn ($c) => ['id' => $c->getId(), 'name' => $c->getName()])->toArray(),
            'image' => $img ? '/uploads/restaurants/'.$img->getFileName() : null,
            'favoriteCount' => $r->getFavoriteCount(),
            'ticketsSold' => $r->getTicketsSold(),
            'url' => $this->generateUrl('app_restaurant_show', ['id' => $r->getId()]),
        ];

        if ($full) {
            $data['description'] = $r->getDescription();
            $data['annualRevenue'] = null !== $r->getAnnualRevenue() ? (float) $r->getAnnualRevenue() : null;
            $data['rent'] = null !== $r->getRent() ? (float) $r->getRent() : null;
            $data['leaseRemaining'] = $r->getLeaseRemaining();
            $data['images'] = $r->getImages()->map(fn ($i) => '/uploads/restaurants/'.$i->getFileName())->toArray();
        }

        return $data;
    }
}
