<?php

namespace App\Controller\Api;

use App\Entity\AiLog;
use App\Entity\Category;
use App\Entity\FaqEntry;
use App\Entity\Restaurant;
use App\Enum\StatusRestaurantEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/internal', name: 'api_internal_')]
class InternalApiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/restaurants', name: 'restaurants', methods: ['GET'])]
    public function restaurants(Request $request): JsonResponse
    {
        $query = $request->query->getString('q') ?: null;
        $category = $request->query->getString('category') ?: null;
        $statusRaw = $request->query->getString('status') ?: null;
        $limit = min((int) $request->query->get('limit', 10), 50);

        $qb = $this->em->createQueryBuilder()
            ->select('r', 'c', 'i')
            ->from(Restaurant::class, 'r')
            ->leftJoin('r.categories', 'c')
            ->leftJoin('r.images', 'i')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($query) {
            $qb->andWhere('LOWER(r.name) LIKE LOWER(:q) OR LOWER(r.address) LIKE LOWER(:q) OR LOWER(r.description) LIKE LOWER(:q)')
                ->setParameter('q', '%'.$query.'%');
        }

        if ($category) {
            $qb->andWhere('LOWER(c.name) LIKE LOWER(:cat) OR LOWER(c.slug) LIKE LOWER(:cat)')
                ->setParameter('cat', '%'.$category.'%');
        }

        if ($statusRaw) {
            $status = StatusRestaurantEnum::tryFrom($statusRaw);
            if ($status) {
                $qb->andWhere('r.status = :status')->setParameter('status', $status);
            }
        }

        /** @var Restaurant[] $restaurants */
        $restaurants = $qb->getQuery()->getResult();

        return $this->json(array_map([$this, 'serializeRestaurant'], $restaurants));
    }

    #[Route('/restaurants/{id}', name: 'restaurant', methods: ['GET'])]
    public function restaurant(int $id): JsonResponse
    {
        $restaurant = $this->em->getRepository(Restaurant::class)->find($id);

        if (!$restaurant) {
            return $this->json(['error' => 'Restaurant introuvable'], 404);
        }

        return $this->json($this->serializeRestaurant($restaurant));
    }

    #[Route('/categories', name: 'categories', methods: ['GET'])]
    public function categories(): JsonResponse
    {
        $categories = $this->em->createQueryBuilder()
            ->select('c')
            ->from(Category::class, 'c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->json(array_map(static fn (Category $c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'slug' => $c->getSlug(),
        ], $categories));
    }

    #[Route('/faq', name: 'faq', methods: ['GET'])]
    public function faq(Request $request): JsonResponse
    {
        $query = $request->query->getString('q') ?: null;

        $qb = $this->em->createQueryBuilder()
            ->select('f')
            ->from(FaqEntry::class, 'f')
            ->orderBy('f.createdAt', 'DESC')
            ->setMaxResults(10);

        if ($query) {
            $keywords = array_filter(
                explode(' ', mb_strtolower($query)),
                static fn (string $w) => mb_strlen($w) > 2
            );

            if ($keywords) {
                $conditions = [];
                foreach (array_values($keywords) as $i => $kw) {
                    $conditions[] = "(LOWER(f.question) LIKE LOWER(:kw{$i}) OR LOWER(f.answer) LIKE LOWER(:kw{$i}))";
                    $qb->setParameter("kw{$i}", '%'.$kw.'%');
                }
                $qb->where(implode(' OR ', $conditions));
            }
        }

        /** @var FaqEntry[] $entries */
        $entries = $qb->getQuery()->getResult();

        return $this->json(array_map(static fn (FaqEntry $f) => [
            'id' => $f->getId(),
            'question' => $f->getQuestion(),
            'answer' => $f->getAnswer(),
        ], $entries));
    }

    #[Route('/ai-logs', name: 'ai_logs', methods: ['GET'])]
    public function aiLogs(Request $request): JsonResponse
    {
        $type = $request->query->getString('type') ?: null;
        $limit = min((int) $request->query->get('limit', 20), 100);

        $qb = $this->em->createQueryBuilder()
            ->select('l')
            ->from(AiLog::class, 'l')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($type) {
            $qb->andWhere('l.type = :type')->setParameter('type', $type);
        }

        /** @var AiLog[] $logs */
        $logs = $qb->getQuery()->getResult();

        return $this->json(array_map(static fn (AiLog $l) => [
            'id' => $l->getId(),
            'type' => $l->getType(),
            'model' => $l->getModel(),
            'prompt' => mb_substr((string) $l->getPrompt(), 0, 200),
            'response' => mb_substr((string) $l->getResponse(), 0, 200),
            'duration' => $l->getDuration(),
            'tokens' => $l->getToken(),
            'createdAt' => $l->getCreatedAt()?->format('c'),
        ], $logs));
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRestaurant(Restaurant $r): array
    {
        return [
            'id' => $r->getId(),
            'name' => $r->getName(),
            'address' => $r->getAddress(),
            'description' => $r->getDescription(),
            'status' => $r->getStatus()->value,
            'capacity' => $r->getCapacity(),
            'askingPrice' => $r->getAskingPrice(),
            'annualRevenue' => $r->getAnnualRevenue(),
            'rent' => $r->getRent(),
            'leaseRemaining' => $r->getLeaseRemaining(),
            'ticketPrice' => $r->getTicketPrice(),
            'auctionDate' => $r->getAuctionDate()?->format('Y-m-d'),
            'auctionLocation' => $r->getAuctionLocation(),
            'categories' => array_map(
                static fn (Category $c) => $c->getName(),
                $r->getCategories()->toArray()
            ),
            'viewCount' => $r->getViewCount(),
            'favoriteCount' => $r->getFavoriteCount(),
            'createdAt' => $r->getCreatedAt()?->format('c'),
        ];
    }
}
