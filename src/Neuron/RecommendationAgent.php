<?php

namespace App\Neuron;

use App\Neuron\Tools\GetRestaurantDetailsTool;
use Doctrine\ORM\EntityManagerInterface;
use NeuronAI\Agent\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Ollama\Ollama;

class RecommendationAgent extends Agent
{
    public static function withTools(EntityManagerInterface $em): static
    {
        $agent = static::make();
        $agent->addTool(new GetRestaurantDetailsTool($em));

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
        $model = $_ENV['OLLAMA_RECOMMEND_MODEL'] ?? $_SERVER['OLLAMA_RECOMMEND_MODEL'] ?? 'recommendation-explainer';

        return new Ollama(
            url: rtrim($baseUrl, '/').'/api',
            model: $model,
        );
    }

    public function instructions(): string
    {
        return <<<PROMPT
            Tu es un conseiller expert en cession de fonds de commerce de restaurants.
            Tu reçois les préférences d'un investisseur et les caractéristiques d'un restaurant mis aux enchères,
            ainsi qu'un score de correspondance.
            Tu rédiges en français une explication courte (2 à 3 phrases maximum, 100 mots maximum), précise et engageante,
            qui justifie pourquoi ce restaurant correspond aux critères de l'investisseur.
            Tu t'appuies uniquement sur les données fournies, sans inventer d'informations.
            Tu adoptes un ton professionnel et positif.
            Ne mentionne pas la similarité cosinus mais simplement la similarité entre les préférences utilisateur et les restaurants.
            Tu ne donnes pas de titre, pas de liste, uniquement du texte continu.
            PROMPT;
    }
}
