<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AiDescriptionService
{
    /** Délai HTTP : avec stream=false, Ollama n’envoie rien tant que la génération n’est pas finie → éviter l’idle timeout Symfony (~60 s). */
    private const GENERATE_TIMEOUT_SECONDS = 600;

    /** Limite de tokens de sortie : réduit le temps de génération (≈ 120–180 mots en français). */
    private const NUM_PREDICT = 320;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $ollamaUrl,
        private readonly string $ollamaModel,
    ) {
    }

    /**
     * @param array<string, mixed> $restaurantData
     */
    public function generateDescription(array $restaurantData): string
    {
        $prompt = $this->buildPrompt($restaurantData);

        $baseUrl = rtrim($this->ollamaUrl, '/');

        $response = $this->httpClient->request('POST', $baseUrl.'/api/generate', [
            'json' => [
                'model' => $this->ollamaModel,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'num_predict' => self::NUM_PREDICT,
                    'temperature' => 0.45,
                ],
            ],
            'timeout' => self::GENERATE_TIMEOUT_SECONDS,
        ]);

        return $this->parseGenerateResponse($response, $baseUrl);
    }

    /**
     * @throws \JsonException
     */
    private function parseGenerateResponse(ResponseInterface $response, string $baseUrl): string
    {
        $statusCode = $response->getStatusCode();
        $raw = $response->getContent(false);

        if ($statusCode >= 400) {
            $detail = $this->extractOllamaError($raw);
            $hint = '';
            if (404 === $statusCode || str_contains(strtolower($detail), 'model') || str_contains(strtolower($detail), 'not found')) {
                $hint = sprintf(
                    ' Crée le modèle dans le conteneur Ollama : `docker compose exec ollama ollama pull phi3:mini` puis `docker compose exec ollama ollama create %s -f /models/Modelfile.content`.',
                    $this->ollamaModel,
                );
            }

            throw new \RuntimeException(sprintf('Ollama (%s/api/generate) HTTP %d — %s.%s', $baseUrl, $statusCode, $detail, $hint));
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        return (string) ($data['response'] ?? '');
    }

    private function extractOllamaError(string $raw): string
    {
        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (isset($decoded['error']) && is_string($decoded['error'])) {
                return $decoded['error'];
            }
        } catch (\JsonException) {
        }

        $trimmed = trim($raw);

        return '' !== $trimmed ? $trimmed : 'réponse vide ou invalide';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildPrompt(array $data): string
    {
        $parts = [
            'Génère une description commerciale courte pour ce restaurant (un seul paragraphe, sans liste à puces) :',
        ];

        if (!empty($data['name'])) {
            $parts[] = "- Nom : {$data['name']}";
        }
        if (!empty($data['address'])) {
            $parts[] = "- Adresse : {$data['address']}";
        }
        if (!empty($data['capacity'])) {
            $parts[] = "- Capacité : {$data['capacity']} couverts";
        }
        if (!empty($data['askingPrice'])) {
            $parts[] = "- Prix de cession : {$data['askingPrice']} €";
        }
        if (!empty($data['annualRevenue'])) {
            $parts[] = "- CA annuel : {$data['annualRevenue']} €";
        }
        if (!empty($data['rent'])) {
            $parts[] = "- Loyer mensuel : {$data['rent']} €";
        }
        if (!empty($data['leaseRemaining'])) {
            $parts[] = "- Bail restant : {$data['leaseRemaining']} ans";
        }
        if (!empty($data['categories'])) {
            $parts[] = '- Types de cuisine : '.implode(', ', $data['categories']);
        }
        if (!empty($data['auctionLocation'])) {
            $parts[] = "- Lieu de l'enchère : {$data['auctionLocation']}";
        }

        $parts[] = 'Reste concis : environ 100 à 160 mots, ton premium.';

        return implode("\n", $parts);
    }
}
