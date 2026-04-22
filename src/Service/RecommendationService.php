<?php

namespace App\Service;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\StatusRestaurantEnum;
use App\Repository\RestaurantEmbeddingRepository;
use App\Repository\RestaurantRepository;
use Psr\Log\LoggerInterface;

class RecommendationService
{
    private const BUDGET_TOLERANCE = 1.20;

    public function __construct(
        private readonly RestaurantEmbeddingRepository $embeddingRepository,
        private readonly RestaurantRepository $restaurantRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<int, array{restaurant: Restaurant, score: float, explanation: string}>
     */
    public function getTopRecommendations(User $user, int $limit = 3): array
    {
        $userEmbedding = $user->getUserPreferenceEmbedding();

        if (!$userEmbedding) {
            return [];
        }

        $prefs = $userEmbedding->getPreferencesData();

        if (empty($prefs)) {
            return [];
        }

        $userVector = $userEmbedding->getEmbedding();
        $hasVectors = !empty($userVector);

        if ($hasVectors) {
            return $this->getRecommendationsWithEmbeddings($userVector, $prefs, $limit);
        }

        return $this->getRecommendationsWithFiltersOnly($prefs, $limit);
    }

    /**
     * @param array<int, float>    $userVector
     * @param array<string, mixed> $prefs
     *
     * @return array<int, array{restaurant: Restaurant, score: float, explanation: string}>
     */
    private function getRecommendationsWithEmbeddings(array $userVector, array $prefs, int $limit): array
    {
        $restaurantEmbeddings = $this->embeddingRepository->findAllWithNonEmptyEmbedding();

        if (empty($restaurantEmbeddings)) {
            return $this->getRecommendationsWithFiltersOnly($prefs, $limit);
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
            $scored[] = ['restaurant' => $restaurant, 'score' => $score];
        }

        if (empty($scored)) {
            foreach ($restaurantEmbeddings as $re) {
                $restaurant = $re->getRestaurant();
                if (!$restaurant) {
                    continue;
                }
                if (!$this->matchesPreferencesSoft($restaurant, $prefs)) {
                    continue;
                }
                $score = $this->cosineSimilarity($userVector, $re->getEmbedding());
                $scored[] = ['restaurant' => $restaurant, 'score' => $score];
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
     * @param array<string, mixed> $prefs
     *
     * @return array<int, array{restaurant: Restaurant, score: float, explanation: string}>
     */
    private function getRecommendationsWithFiltersOnly(array $prefs, int $limit): array
    {
        // Récupère tous les restaurants publiés/programmés directement
        $allRestaurants = $this->restaurantRepository->findBy([
            'status' => [StatusRestaurantEnum::PUBLIE, StatusRestaurantEnum::PROGRAMME],
        ]);

        if (empty($allRestaurants)) {
            return [];
        }

        $scored = [];

        // Filtrage strict
        foreach ($allRestaurants as $restaurant) {
            if ($this->matchesPreferences($restaurant, $prefs)) {
                $score = $this->computeSimpleScore($restaurant, $prefs);
                $scored[] = ['restaurant' => $restaurant, 'score' => $score];
            }
        }

        // Fallback assoupli si aucun résultat
        if (empty($scored)) {
            $this->logger->info('RecommendationService (no embeddings): fallback soft filters.');
            foreach ($allRestaurants as $restaurant) {
                if ($this->matchesPreferencesSoft($restaurant, $prefs)) {
                    $score = $this->computeSimpleScore($restaurant, $prefs);
                    $scored[] = ['restaurant' => $restaurant, 'score' => $score];
                }
            }
        }

        // Dernier fallback : tous les restaurants si toujours vide
        if (empty($scored)) {
            foreach ($allRestaurants as $restaurant) {
                $scored[] = [
                    'restaurant' => $restaurant,
                    'score' => 0.5,
                ];
            }
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
     * @param array<string, mixed> $prefs
     */
    private function computeSimpleScore(Restaurant $restaurant, array $prefs): float
    {
        $score = 0.5;

        if (!empty($prefs['budgetMin']) && !empty($prefs['budgetMax']) && null !== $restaurant->getAskingPrice()) {
            $price = (float) $restaurant->getAskingPrice();
            $min = (float) $prefs['budgetMin'];
            $max = (float) $prefs['budgetMax'];
            $mid = ($min + $max) / 2;
            $range = max($max - $min, 1);
            $score += 0.3 * (1 - abs($price - $mid) / $range);
        }

        if (!empty($prefs['cuisineTypes'])) {
            $restaurantCategories = array_map(
                static fn ($c) => mb_strtolower($c->getName() ?? ''),
                $restaurant->getCategories()->toArray()
            );
            $preferredCuisines = array_map(static fn ($c) => mb_strtolower((string) $c), (array) $prefs['cuisineTypes']);

            foreach ($preferredCuisines as $pref) {
                foreach ($restaurantCategories as $cat) {
                    if (str_contains($cat, $pref) || str_contains($pref, $cat)) {
                        $score += 0.2;
                        break 2;
                    }
                }
            }
        }

        return min(1.0, $score);
    }

    /**
     * @param array<string, mixed> $prefs
     */
    private function matchesPreferences(Restaurant $restaurant, array $prefs): bool
    {
        if (!$this->matchesBudget($restaurant, $prefs)) {
            return false;
        }

        if (!empty($prefs['capacityMin']) && null !== $restaurant->getCapacity()) {
            if ($restaurant->getCapacity() < (int) $prefs['capacityMin']) {
                return false;
            }
        }

        if (!empty($prefs['cuisineTypes'])) {
            $restaurantCategories = array_map(
                static fn ($c) => mb_strtolower($c->getName() ?? ''),
                $restaurant->getCategories()->toArray()
            );
            $preferredCuisines = array_map(static fn ($c) => mb_strtolower((string) $c), (array) $prefs['cuisineTypes']);

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
            $city = mb_strtolower((string) $prefs['preferredCity']);
            $address = mb_strtolower((string) $restaurant->getAddress());
            if (!str_contains($address, $city)) {
                return false;
            }
        }

        return true;
    }

    /**
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
            return true;
        }

        if (!empty($prefs['budgetMin']) && $price < (float) $prefs['budgetMin']) {
            return false;
        }

        if (!empty($prefs['budgetMax']) && $price > (float) $prefs['budgetMax'] * self::BUDGET_TOLERANCE) {
            return false;
        }

        return true;
    }

    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * asin(sqrt($a));
    }

    /**
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
