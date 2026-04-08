<?php

namespace App\Neuron;

use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Ollama\Ollama;

class RestaurantDescriptionAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
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
            Tu rédiges en français, entre 150 et 300 mots, avec un ton premium et engageant.
            PROMPT;
    }
}
