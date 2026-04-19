<?php

namespace App\Neuron\Tools;

use App\Entity\Category;
use App\Entity\Restaurant;
use Doctrine\ORM\EntityManagerInterface;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

class GetRestaurantDetailsTool extends Tool
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct(
            name: 'get_restaurant_details',
            description: 'Récupère le détail complet d\'un restaurant par son identifiant : description, données financières, informations sur l\'enchère et catégories.',
        );
    }

    protected function properties(): array
    {
        return [
            ToolProperty::make(
                name: 'id',
                type: PropertyType::NUMBER,
                description: 'Identifiant numérique du restaurant',
                required: true,
            ),
        ];
    }

    public function __invoke(int $id): string
    {
        $restaurant = $this->em->getRepository(Restaurant::class)->find($id);

        if (!$restaurant) {
            return json_encode(['error' => "Restaurant {$id} introuvable"]) ?: '{}';
        }

        $data = [
            'id' => $restaurant->getId(),
            'name' => $restaurant->getName(),
            'address' => $restaurant->getAddress(),
            'description' => $restaurant->getDescription(),
            'status' => $restaurant->getStatus()->value,
            'capacity' => $restaurant->getCapacity(),
            'askingPrice' => $restaurant->getAskingPrice(),
            'annualRevenue' => $restaurant->getAnnualRevenue(),
            'rent' => $restaurant->getRent(),
            'leaseRemaining' => $restaurant->getLeaseRemaining(),
            'ticketPrice' => $restaurant->getTicketPrice(),
            'auctionDate' => $restaurant->getAuctionDate()?->format('Y-m-d'),
            'auctionLocation' => $restaurant->getAuctionLocation(),
            'categories' => array_map(
                static fn (Category $c) => $c->getName(),
                $restaurant->getCategories()->toArray()
            ),
        ];

        return json_encode($data, JSON_UNESCAPED_UNICODE) ?: '{}';
    }
}
