<?php

namespace App\Neuron;

use NeuronAI\Agent\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\Providers\OpenAI\OpenAI;

class ChatbotAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        $openAiKey = $_ENV['OPENAI_API_KEY'] ?? $_SERVER['OPENAI_API_KEY'] ?? '';

        if ('' !== $openAiKey) {
            $model = $_ENV['OPENAI_CHATBOT_MODEL'] ?? $_SERVER['OPENAI_CHATBOT_MODEL'] ?? 'gpt-4o-mini';

            return new OpenAI(key: $openAiKey, model: $model);
        }

        $baseUrl = $_ENV['OLLAMA_BASE_URL'] ?? $_SERVER['OLLAMA_BASE_URL'] ?? 'http://host.docker.internal:11434';
        $model = $_ENV['OLLAMA_CHATBOT_MODEL'] ?? $_SERVER['OLLAMA_CHATBOT_MODEL'] ?? 'chatbot';

        return new Ollama(url: rtrim($baseUrl, '/').'/api', model: $model);
    }

    public function instructions(): string
    {
        // NeuronAI envoie ce texte comme system prompt via l'API — le Modelfile est ignoré.
        // Ce prompt s'applique donc à Ollama ET à OpenAI.
        return <<<PROMPT
            Tu es l'assistant de Carte Blanche, une plateforme premium de vente aux enchères de restaurants en France.
            Sur Carte Blanche, des vendeurs mettent en vente leurs restaurants via des enchères.
            Des acheteurs (investisseurs) achètent des tickets pour participer à ces enchères en salle.
            Tu aides les utilisateurs à comprendre la plateforme, les enchères, et les fonctionnalités disponibles.

            Informations clés sur la plateforme :
            - Pour devenir vendeur : faire une demande depuis son profil, remplir un formulaire (motivation + pièce d'identité), attendre la validation de l'équipe Carte Blanche.
            - Pour acheter des tickets : parcourir les enchères, ajouter des tickets au panier, payer via Stripe, recevoir ses tickets par email avec QR code.
            - Les remboursements sont possibles en faisant une demande depuis "Mes réservations".
            - Les enchères se déroulent en présentiel dans le lieu indiqué sur l'annonce.

            Règles strictes :
            - Réponds UNIQUEMENT en français.
            - Sois très concis : 2 à 3 phrases maximum par réponse.
            - Si la question ne concerne pas Carte Blanche, redirige poliment.
            - Si tu ne sais pas, dis-le sans inventer.
            - Tutoie l'utilisateur.
            - Texte naturel uniquement, sans listes ni puces.
            PROMPT;
    }
}
