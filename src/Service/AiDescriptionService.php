<?php

namespace App\Service;

use App\Neuron\RestaurantDescriptionAgent;
use NeuronAI\Chat\Messages\UserMessage;

class AiDescriptionService
{
    /**
     * @param array<string, mixed> $restaurantData
     */
    public function generateDescription(array $restaurantData): string
    {
        $agent = RestaurantDescriptionAgent::make();

        // chat() retourne un AgentHandler, run() retourne l'AgentState
        $handler = $agent->chat(new UserMessage($this->buildPrompt($restaurantData)));
        $state = $handler->run();

        // Le dernier message de l'état contient la réponse
        $messages = $state->get('messages') ?? [];
        if (empty($messages)) {
            throw new \RuntimeException('Aucun message retourné par le modèle');
        }
        $last = end($messages);
        if (false === $last) {
            throw new \RuntimeException('Impossible de lire le dernier message');
        }

        return $last->getContent() ?? '';
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
            $parts[] = "- Lieu enchère : {$data['auctionLocation']}";
        }

        return implode("\n", $parts);
    }
}
