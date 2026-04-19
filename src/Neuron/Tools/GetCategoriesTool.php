<?php

namespace App\Neuron\Tools;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use NeuronAI\Tools\Tool;

class GetCategoriesTool extends Tool
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct(
            name: 'get_categories',
            description: 'Retourne la liste de toutes les catégories de cuisine disponibles sur Carte Blanche (ex: français, italien, japonais, végétarien…).',
        );
    }

    public function __invoke(): string
    {
        /** @var Category[] $categories */
        $categories = $this->em->createQueryBuilder()
            ->select('c')
            ->from(Category::class, 'c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        $results = array_map(static fn (Category $c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'slug' => $c->getSlug(),
        ], $categories);

        return json_encode($results, JSON_UNESCAPED_UNICODE) ?: '[]';
    }
}
