<?php

namespace App\Neuron\Tools;

use App\Entity\FaqEntry;
use Doctrine\ORM\EntityManagerInterface;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

class SearchFaqTool extends Tool
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct(
            name: 'search_faq',
            description: 'Recherche dans la FAQ de Carte Blanche. Retourne les questions/réponses pertinentes sur le fonctionnement de la plateforme, les enchères, les tickets et les remboursements.',
        );
    }

    protected function properties(): array
    {
        return [
            ToolProperty::make(
                name: 'query',
                type: PropertyType::STRING,
                description: 'Question ou mots-clés à rechercher dans la FAQ',
                required: true,
            ),
        ];
    }

    public function __invoke(string $query): string
    {
        $keywords = array_filter(
            explode(' ', mb_strtolower($query)),
            static fn (string $w) => mb_strlen($w) > 2
        );

        if (empty($keywords)) {
            return json_encode([]) ?: '[]';
        }

        $qb = $this->em->createQueryBuilder()
            ->select('f')
            ->from(FaqEntry::class, 'f')
            ->setMaxResults(5);

        $conditions = [];
        foreach (array_values($keywords) as $i => $kw) {
            $conditions[] = "(LOWER(f.question) LIKE LOWER(:kw{$i}) OR LOWER(f.answer) LIKE LOWER(:kw{$i}))";
            $qb->setParameter("kw{$i}", '%'.$kw.'%');
        }
        $qb->where(implode(' OR ', $conditions));

        /** @var FaqEntry[] $entries */
        $entries = $qb->getQuery()->getResult();

        $results = array_map(static fn (FaqEntry $f) => [
            'question' => $f->getQuestion(),
            'answer' => $f->getAnswer(),
        ], $entries);

        return json_encode($results, JSON_UNESCAPED_UNICODE) ?: '[]';
    }
}
