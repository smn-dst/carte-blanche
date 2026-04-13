<?php

namespace App\Service;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Neuron\RecommendationAgent;
use App\Repository\RestaurantEmbeddingRepository;
use NeuronAI\Chat\Messages\UserMessage;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class RecommendationService
{
    // TTL du cache Redis pour les explications LLM (1 heure)
    private const EXPLANATION_TTL = 3600;

    public function __construct(
        private readonly RestaurantEmbeddingRepository $embeddingRepository,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Retourne le top-$limit des restaurants les plus proches des préférences utilisateur.
     *
     * @return array<int, array{restaurant: Restaurant, score: float, explanation: string}>
     */
    public function getTopRecommendations(User $user, int $limit = 3): array
    {
        $userEmbedding = $user->getUserPreferenceEmbedding();

        if (!$userEmbedding || empty($userEmbedding->getEmbedding())) {
            return [];
        }

        $userVector = $userEmbedding->getEmbedding();
        $restaurantEmbeddings = $this->embeddingRepository->findAllWithNonEmptyEmbedding();

        if (empty($restaurantEmbeddings)) {
            return [];
        }

        // Calcul de la similarité cosinus pour chaque restaurant
        $scored = [];
        foreach ($restaurantEmbeddings as $re) {
            $restaurant = $re->getRestaurant();
            if (!$restaurant) {
                continue;
            }

            $score = $this->cosineSimilarity($userVector, $re->getEmbedding());
            $scored[] = [
                'restaurant' => $restaurant,
                'score' => $score,
                'content' => $re->getContent(),
            ];
        }

        // Tri par score décroissant
        usort($scored, static fn (array $a, array $b) => $b['score'] <=> $a['score']);

        $top = array_slice($scored, 0, $limit);

        // Génération ou récupération (cache Redis) des explications LLM
        $results = [];
        foreach ($top as $item) {
            $explanation = $this->getExplanation(
                $user,
                $item['restaurant'],
                $userEmbedding->getPreferencesText() ?? '',
                $item['content'] ?? '',
                $item['score'],
            );

            $results[] = [
                'restaurant' => $item['restaurant'],
                'score' => $item['score'],
                'explanation' => $explanation,
            ];
        }

        return $results;
    }

    /**
     * Calcule la similarité cosinus entre deux vecteurs.
     * Retourne 0.0 si l'un des vecteurs est vide ou nul.
     *
     * @param array<int, float> $a
     * @param array<int, float> $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        if (empty($a) || empty($b) || count($a) !== count($b)) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $valA) {
            $valB = $b[$i] ?? 0.0;
            $dot += (float) $valA * (float) $valB;
            $normA += (float) $valA * (float) $valA;
            $normB += (float) $valB * (float) $valB;
        }

        $denominator = sqrt($normA) * sqrt($normB);
        if ($denominator < PHP_FLOAT_EPSILON) {
            return 0.0;
        }

        return $dot / $denominator;
    }

    /**
     * Génère ou récupère depuis le cache l'explication LLM du match restaurant/utilisateur.
     */
    private function getExplanation(
        User $user,
        Restaurant $restaurant,
        string $preferencesText,
        string $restaurantContent,
        float $score,
    ): string {
        $cacheKey = sprintf('rec_exp_u%d_r%d', $user->getId(), $restaurant->getId());

        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            return (string) $item->get();
        }

        $explanation = $this->generateExplanation($preferencesText, $restaurantContent, $score);

        $item->set($explanation);
        $item->expiresAfter(self::EXPLANATION_TTL);
        $this->cache->save($item);

        return $explanation;
    }

    /**
     * Appelle le RecommendationAgent pour générer l'explication LLM.
     */
    private function generateExplanation(
        string $preferencesText,
        string $restaurantContent,
        float $score,
    ): string {
        $scorePercent = round($score * 100, 1);

        $prompt = <<<PROMPT
            Préférences de l'investisseur : {$preferencesText}

            Caractéristiques du restaurant : {$restaurantContent}

            Score de correspondance (similarité cosinus) : {$scorePercent}%

            Explique en 2 à 3 phrases pourquoi ce restaurant correspond aux préférences de cet investisseur.
            PROMPT;

        try {
            $agent = RecommendationAgent::make();
            $message = $agent->chat(new UserMessage($prompt))->getMessage();

            return trim((string) $message->getContent());
        } catch (\Throwable $e) {
            $this->logger->error('RecommendationService: échec génération explication LLM: {message}', [
                'message' => $e->getMessage(),
            ]);

            // Fallback : explication générique sans LLM
            return sprintf(
                'Ce restaurant présente un taux de correspondance de %s%% avec vos critères de recherche.',
                round($score * 100)
            );
        }
    }
}
