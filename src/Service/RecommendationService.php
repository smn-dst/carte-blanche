<?php

namespace App\Service;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Repository\RestaurantEmbeddingRepository;
use Psr\Log\LoggerInterface;

class RecommendationService
{
    // Tolérance budget : ±20% au-delà du max (pour ne pas exclure des bons candidats proches)
    private const BUDGET_TOLERANCE = 1.20;

    public function __construct(
        private readonly RestaurantEmbeddingRepository $embeddingRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Retourne le top-$limit des restaurants correspondant aux préférences utilisateur.
     * Étape 1 : pré-filtrage dur sur budget, capacité, localisation, cuisine.
     * Étape 2 : ranking par similarité cosinus sur les candidats restants.
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
        $prefs = $userEmbedding->getPreferencesData();
        $restaurantEmbeddings = $this->embeddingRepository->findAllWithNonEmptyEmbedding();

        if (empty($restaurantEmbeddings)) {
            return [];
        }

        $scored = [];

        foreach ($restaurantEmbeddings as $re) {
            $restaurant = $re->getRestaurant();
            if (!$restaurant) {
                continue;
            }

            if (!$this->matchesPreferences($restaurant, $prefs)) {
                continue;
            }

            $score = $this->cosineSimilarity($userVector, $re->getEmbedding());
            $scored[] = [
                'restaurant' => $restaurant,
                'score' => $score,
            ];
        }

        // Si aucun résultat après filtrage strict → on assouplit (sans filtre cuisine/localisation)
        if (empty($scored)) {
            $this->logger->info('RecommendationService: aucun résultat avec filtres stricts, fallback sans filtre cuisine/ville.');

            foreach ($restaurantEmbeddings as $re) {
                $restaurant = $re->getRestaurant();
                if (!$restaurant) {
                    continue;
                }

                if (!$this->matchesPreferencesSoft($restaurant, $prefs)) {
                    continue;
                }

                $score = $this->cosineSimilarity($userVector, $re->getEmbedding());
                $scored[] = [
                    'restaurant' => $restaurant,
                    'score' => $score,
                ];
            }
        }

        if (empty($scored)) {
            return [];
        }

        usort($scored, static fn (array $a, array $b) => $b['score'] <=> $a['score']);

        $results = [];
        foreach (array_slice($scored, 0, $limit) as $item) {
            $results[] = [
                'restaurant' => $item['restaurant'],
                'score' => $item['score'],
                'explanation' => $this->buildExplanation($item['restaurant'], $item['score'], $prefs),
            ];
        }

        return $results;
    }

    /**
     * Filtrage strict : budget + capacité + cuisine + localisation (si lat/lng dispo).
     *
     * @param array<string, mixed> $prefs
     */
    private function matchesPreferences(Restaurant $restaurant, array $prefs): bool
    {
        // Budget
        if (!$this->matchesBudget($restaurant, $prefs)) {
            return false;
        }

        // Capacité minimum
        if (!empty($prefs['capacityMin']) && null !== $restaurant->getCapacity()) {
            if ($restaurant->getCapacity() < (int) $prefs['capacityMin']) {
                return false;
            }
        }

        // Type de cuisine
        if (!empty($prefs['cuisineTypes'])) {
            $restaurantCategories = array_map(
                static fn ($c) => mb_strtolower($c->getName() ?? ''),
                $restaurant->getCategories()->toArray()
            );

            $preferredCuisines = array_map(
                static fn ($c) => mb_strtolower((string) $c),
                (array) $prefs['cuisineTypes']
            );

            $hasMatchingCuisine = false;
            foreach ($preferredCuisines as $pref) {
                foreach ($restaurantCategories as $cat) {
                    if (str_contains($cat, $pref) || str_contains($pref, $cat)) {
                        $hasMatchingCuisine = true;
                        break 2;
                    }
                }
            }

            if (!$hasMatchingCuisine) {
                return false;
            }
        }

        // Localisation par distance haversine si lat/lng disponibles
        if (!empty($prefs['preferredLat']) && !empty($prefs['preferredLng']) && !empty($prefs['searchRadius'])) {
            if (null !== $restaurant->getLatitude() && null !== $restaurant->getLongitude()) {
                $distance = $this->haversineDistance(
                    (float) $prefs['preferredLat'],
                    (float) $prefs['preferredLng'],
                    $restaurant->getLatitude(),
                    $restaurant->getLongitude()
                );

                if ($distance > (int) $prefs['searchRadius']) {
                    return false;
                }
            }
        } elseif (!empty($prefs['preferredCity'])) {
            // Fallback textuel si pas de coordonnées
            $city = mb_strtolower((string) $prefs['preferredCity']);
            $address = mb_strtolower((string) $restaurant->getAddress());
            if (!str_contains($address, $city)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Filtrage assoupli : budget + capacité uniquement (sans cuisine ni localisation).
     *
     * @param array<string, mixed> $prefs
     */
    private function matchesPreferencesSoft(Restaurant $restaurant, array $prefs): bool
    {
        if (!$this->matchesBudget($restaurant, $prefs)) {
            return false;
        }

        if (!empty($prefs['capacityMin']) && null !== $restaurant->getCapacity()) {
            if ($restaurant->getCapacity() < (int) $prefs['capacityMin']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $prefs
     */
    private function matchesBudget(Restaurant $restaurant, array $prefs): bool
    {
        $price = null !== $restaurant->getAskingPrice() ? (float) $restaurant->getAskingPrice() : null;

        if (null === $price) {
            return true; // Pas de prix → on inclut
        }

        if (!empty($prefs['budgetMin']) && $price < (float) $prefs['budgetMin']) {
            return false;
        }

        // Tolérance de 20% au-dessus du budget max
        if (!empty($prefs['budgetMax']) && $price > (float) $prefs['budgetMax'] * self::BUDGET_TOLERANCE) {
            return false;
        }

        return true;
    }

    /**
     * Distance haversine en km entre deux points GPS.
     */
    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * asin(sqrt($a));
    }

    /**
     * Explication courte sans LLM.
     *
     * @param array<string, mixed> $prefs
     */
    private function buildExplanation(Restaurant $restaurant, float $score, array $prefs): string
    {
        $percent = (int) round($score * 100);
        $qualifier = match (true) {
            $percent >= 85 => 'correspond parfaitement',
            $percent >= 70 => 'correspond très bien',
            $percent >= 55 => 'correspond bien',
            default => 'correspond',
        };

        $parts = ["Ce restaurant {$qualifier} à tes critères ({$percent}% de compatibilité)."];

        if ($restaurant->getAnnualRevenue()) {
            $ca = number_format((float) $restaurant->getAnnualRevenue(), 0, ',', ' ');
            $parts[] = "CA annuel : {$ca} €.";
        } elseif ($restaurant->getCapacity()) {
            $parts[] = "Capacité : {$restaurant->getCapacity()} couverts.";
        }

        return implode(' ', $parts);
    }

    /**
     * @param array<int, float> $a
     * @param array<int, float> $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        if (empty($a) || empty($b) || count($a) !== count($b)) {
            return 0.0;
        }

        $dot = $normA = $normB = 0.0;

        foreach ($a as $i => $valA) {
            $valB = $b[$i] ?? 0.0;
            $dot += (float) $valA * (float) $valB;
            $normA += (float) $valA * (float) $valA;
            $normB += (float) $valB * (float) $valB;
        }

        $denom = sqrt($normA) * sqrt($normB);

        return $denom < PHP_FLOAT_EPSILON ? 0.0 : $dot / $denom;
    }
}
