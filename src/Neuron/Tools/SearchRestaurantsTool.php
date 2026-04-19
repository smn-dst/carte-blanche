<?php

namespace App\Neuron\Tools;

use App\Entity\Category;
use App\Entity\Restaurant;
use App\Enum\StatusRestaurantEnum;
use Doctrine\ORM\EntityManagerInterface;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

class SearchRestaurantsTool extends Tool
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct(
            name: 'search_restaurants',
            description: 'Recherche des restaurants sur Carte Blanche. Permet de filtrer par texte, catégorie de cuisine et statut. Retourne les informations clés : nom, adresse, prix, CA, capacité, date d\'enchère.',
        );
    }

    protected function properties(): array
    {
        return [
            ToolProperty::make(
                name: 'query',
                type: PropertyType::STRING,
                description: 'Texte libre pour chercher par nom, adresse ou description',
                required: false,
            ),
            ToolProperty::make(
                name: 'category',
                type: PropertyType::STRING,
                description: 'Filtre par catégorie de cuisine (ex: "italien", "japonais", "français")',
                required: false,
            ),
            ToolProperty::make(
                name: 'limit',
                type: PropertyType::NUMBER,
                description: 'Nombre maximum de résultats (défaut 5, max 20)',
                required: false,
            ),
        ];
    }

    public function __invoke(
        ?string $query = null,
        ?string $category = null,
        int $limit = 5,
    ): string {
        $limit = min($limit, 20);

        $qb = $this->em->createQueryBuilder()
            ->select('r', 'c')
            ->from(Restaurant::class, 'r')
            ->leftJoin('r.categories', 'c')
            ->andWhere('r.status = :status')
            ->setParameter('status', StatusRestaurantEnum::PUBLIE)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($query) {
            $qb->andWhere('LOWER(r.name) LIKE LOWER(:q) OR LOWER(r.address) LIKE LOWER(:q)')
                ->setParameter('q', '%'.$query.'%');
        }

        if ($category) {
            $qb->andWhere('LOWER(c.name) LIKE LOWER(:cat) OR LOWER(c.slug) LIKE LOWER(:cat)')
                ->setParameter('cat', '%'.$category.'%');
        }

        /** @var Restaurant[] $restaurants */
        $restaurants = $qb->getQuery()->getResult();

        $results = array_map(static fn (Restaurant $r) => [
            'id' => $r->getId(),
            'name' => $r->getName(),
            'address' => $r->getAddress(),
            'capacity' => $r->getCapacity(),
            'askingPrice' => $r->getAskingPrice(),
            'annualRevenue' => $r->getAnnualRevenue(),
            'auctionDate' => $r->getAuctionDate()?->format('Y-m-d'),
            'categories' => array_map(
                static fn (Category $c) => $c->getName(),
                $r->getCategories()->toArray()
            ),
        ], $restaurants);

        return json_encode($results, JSON_UNESCAPED_UNICODE) ?: '[]';
    }
}
