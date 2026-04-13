<?php

namespace App\Neuron;

use NeuronAI\Agent\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Ollama\Ollama;

class RecommendationAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        $baseUrl = $_ENV['OLLAMA_URL'] ?? $_SERVER['OLLAMA_URL'] ?? 'http://ollama:11434';
        $model = $_ENV['OLLAMA_RECOMMEND_MODEL'] ?? $_SERVER['OLLAMA_RECOMMEND_MODEL'] ?? 'recommendation-explainer';

        return new Ollama(
            url: rtrim($baseUrl, '/').'/api',
            model: $model,
        );
    }

    public function instructions(): string
    {
        // Le system prompt est défini dans le Modelfile.recommendation
        return '';
    }
}
