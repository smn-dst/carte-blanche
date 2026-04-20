<?php

namespace App\Neuron;

use App\Neuron\Tools\GetRestaurantDataTool;
use Doctrine\ORM\EntityManagerInterface;
use NeuronAI\Agent\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Ollama\Ollama;

class RestaurantDescriptionAgent extends Agent
{
    public static function withTools(EntityManagerInterface $em): static
    {
        $agent = static::make();
        $agent->addTool(new GetRestaurantDataTool($em));

        return $agent;
    }

    protected function provider(): AIProviderInterface
    {
        // Groq (prioritaire)
        $groqKey = $_ENV['GROQ_API_KEY'] ?? $_SERVER['GROQ_API_KEY'] ?? '';
        if ('' !== $groqKey) {
            $model = $_ENV['GROQ_MODEL'] ?? $_SERVER['GROQ_MODEL'] ?? 'llama-3.3-70b-versatile';

            return new GroqOpenAI(key: $groqKey, model: $model);
        }

        // Ollama (local dev)
        $baseUrl = $_ENV['OLLAMA_URL'] ?? $_SERVER['OLLAMA_URL'] ?? 'http://ollama:11434';
        $model = $_ENV['OLLAMA_MODEL'] ?? $_SERVER['OLLAMA_MODEL'] ?? 'content-generator';

        return new Ollama(
            url: rtrim($baseUrl, '/').'/api',
            model: $model,
        );
    }

    public function instructions(): string
    {
        return <<<PROMPT
            Tu es un expert en rédaction commerciale pour la restauration.
            Tu génères des descriptions attractives et professionnelles de fonds de commerce de restaurants.
            Tu t'appuies uniquement sur les informations fournies, sans inventer.
            Tu rédiges en français, 100 mots maximum, avec un ton premium et engageant, et pas de listes à puces.
            PROMPT;
    }
}
