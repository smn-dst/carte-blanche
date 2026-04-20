<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiDescriptionService
{
    private const GENERATE_TIMEOUT_SECONDS = 60;
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

        $groqKey = $_ENV['GROQ_API_KEY'] ?? $_SERVER['GROQ_API_KEY'] ?? '';

        if ('' !== $groqKey) {
            return $this->generateWithGroq($prompt, $groqKey);
        }

        return $this->generateWithOllama($prompt);
    }

    private function generateWithGroq(string $prompt, string $apiKey): string
    {
        $model = $_ENV['GROQ_MODEL'] ?? $_SERVER['GROQ_MODEL'] ?? 'llama-3.3-70b-versatile';

        $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un expert en rédaction commerciale pour la restauration. Tu génères des descriptions attractives et professionnelles de fonds de commerce de restaurants. Tu rédiges en français, 100 mots maximum, ton premium, sans listes à puces.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => self::NUM_PREDICT,
                'temperature' => 0.45,
            ],
            'timeout' => self::GENERATE_TIMEOUT_SECONDS,
        ]);

        $statusCode = $response->getStatusCode();
        $raw = $response->getContent(false);

        if ($statusCode >= 400) {
            throw new \RuntimeException(sprintf('Groq API HTTP %d — %s', $statusCode, $raw));
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        return (string) ($data['choices'][0]['message']['content'] ?? '');
    }

    private function generateWithOllama(string $prompt): string
    {
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
            'timeout' => 600,
        ]);

        $statusCode = $response->getStatusCode();
        $raw = $response->getContent(false);

        if ($statusCode >= 400) {
            throw new \RuntimeException(sprintf('Ollama HTTP %d — %s', $statusCode, $raw));
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        return (string) ($data['response'] ?? '');
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
