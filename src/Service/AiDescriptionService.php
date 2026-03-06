<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiDescriptionService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $ollamaUrl = 'http://host.docker.internal:11434',
    ) {
    }

    /**
     * @param array<string, mixed> $restaurantData
     */
    public function generateDescription(array $restaurantData): string
    {
        $prompt = $this->buildPrompt($restaurantData);

        $response = $this->httpClient->request('POST', $this->ollamaUrl.'/api/generate', [
            'json' => [
                'model' => 'content-generator',
                'prompt' => $prompt,
                'stream' => false,
            ],
        ]);

        $data = $response->toArray();

        return $data['response'] ?? '';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildPrompt(array $data): string
    {
        $parts = ['Génère une description commerciale pour ce restaurant :'];

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

        return implode("\n", $parts);
    }
}