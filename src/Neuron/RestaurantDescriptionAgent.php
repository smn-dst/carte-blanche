<?php

namespace App\Neuron;

use NeuronAI\Agent\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Ollama\Ollama;

class RestaurantDescriptionAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return new Ollama(
            url: ($_ENV['OLLAMA_URL'] ?? 'http://host.docker.internal:11434').'/api',
            model: 'content-generator',
        );
    }

    protected function instructions(): string
    {
        return 'Tu es un expert en rédaction commerciale pour la restauration.'
            .'Tu génères des descriptions attractives et professionnelles de fonds de commerce de restaurants.'
            .'Tu rédiges en français, entre 150 et 300 mots, avec un ton premium et engageant.'
            .'Tu t\'appuies uniquement sur les informations fournies, sans rien inventer.';
    }
}
