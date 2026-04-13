<?php

namespace App\Service;

use App\Entity\Restaurant;
use App\Entity\RestaurantEmbedding;
use App\Entity\UserPreferenceEmbedding;
use App\Repository\RestaurantEmbeddingRepository;
use Doctrine\ORM\EntityManagerInterface;
use NeuronAI\RAG\Embeddings\OllamaEmbeddingsProvider;
use Psr\Log\LoggerInterface;

class EmbeddingService
{
    private OllamaEmbeddingsProvider $embeddingsProvider;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RestaurantEmbeddingRepository $restaurantEmbeddingRepository,
        private readonly LoggerInterface $logger,
    ) {
        $baseUrl = $_ENV['OLLAMA_URL'] ?? $_SERVER['OLLAMA_URL'] ?? 'http://ollama:11434';
        $model = $_ENV['OLLAMA_EMBED_MODEL'] ?? $_SERVER['OLLAMA_EMBED_MODEL'] ?? 'nomic-embed-text';

        $this->embeddingsProvider = new OllamaEmbeddingsProvider(
            model: $model,
            url: rtrim($baseUrl, '/').'/api',
        );
    }

    /**
     * Génère un vecteur d'embedding pour un texte libre.
     *
     * @return array<int, float>
     */
    public function embedText(string $text): array
    {
        return $this->embeddingsProvider->embedText($text);
    }

    /**
     * Génère et persiste l'embedding d'un restaurant.
     * Crée ou met à jour le RestaurantEmbedding associé.
     */
    public function embedRestaurant(Restaurant $restaurant): void
    {
        $content = $this->buildRestaurantContent($restaurant);

        $embedding = $this->restaurantEmbeddingRepository->findOneBy(['restaurant' => $restaurant])
            ?? new RestaurantEmbedding();

        try {
            $vector = $this->embedText($content);
        } catch (\Throwable $e) {
            $this->logger->error('EmbeddingService: échec génération embedding restaurant {id}: {message}', [
                'id' => $restaurant->getId(),
                'message' => $e->getMessage(),
            ]);

            return;
        }

        $embedding->setRestaurant($restaurant);
        $embedding->setContent($content);
        $embedding->setEmbedding($vector);
        $embedding->setUpdatedAt(new \DateTimeImmutable());

        $this->em->persist($embedding);
        $this->em->flush();
    }

    /**
     * Génère et persiste l'embedding des préférences d'un utilisateur.
     */
    public function embedUserPreferences(UserPreferenceEmbedding $pref): void
    {
        $text = $pref->getPreferencesText();
        if (!$text) {
            return;
        }

        try {
            $vector = $this->embedText($text);
        } catch (\Throwable $e) {
            $this->logger->error('EmbeddingService: échec génération embedding user preferences {id}: {message}', [
                'id' => $pref->getId(),
                'message' => $e->getMessage(),
            ]);

            return;
        }

        $pref->setEmbedding($vector);
        $pref->setUpdatedAt(new \DateTimeImmutable());

        $this->em->persist($pref);
        $this->em->flush();
    }

    /**
     * Construit le texte de contenu d'un restaurant pour l'embedding.
     */
    private function buildRestaurantContent(Restaurant $restaurant): string
    {
        $parts = [];

        $parts[] = 'Nom : '.$restaurant->getName();

        $categories = $restaurant->getCategories()->map(fn ($c) => $c->getName())->toArray();
        if (!empty($categories)) {
            $parts[] = 'Types de cuisine : '.implode(', ', $categories);
        }

        if ($restaurant->getAddress()) {
            $parts[] = 'Localisation : '.$restaurant->getAddress();
        }

        if ($restaurant->getCapacity()) {
            $parts[] = 'Capacité : '.$restaurant->getCapacity().' couverts';
        }

        if ($restaurant->getAskingPrice()) {
            $parts[] = 'Prix de cession : '.number_format((float) $restaurant->getAskingPrice(), 0, ',', ' ').' €';
        }

        if ($restaurant->getAnnualRevenue()) {
            $parts[] = 'Chiffre d\'affaires annuel : '.number_format((float) $restaurant->getAnnualRevenue(), 0, ',', ' ').' €';
        }

        if ($restaurant->getDescription()) {
            $parts[] = 'Description : '.$restaurant->getDescription();
        }

        return implode('. ', $parts);
    }
}
