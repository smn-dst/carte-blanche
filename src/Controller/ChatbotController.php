<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ChatbotController extends AbstractController
{
    public function __construct(
        private readonly ChatbotService $chatbotService,
    ) {
    }

    #[Route('/chatbot/ask', name: 'app_chatbot_ask', methods: ['POST'])]
    public function ask(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $question = trim((string) ($data['question'] ?? ''));
        /** @var array<array{role: string, content: string}> $history */
        $history = (array) ($data['history'] ?? []);

        if ('' === $question) {
            return $this->json(['error' => 'Question vide.'], 400);
        }

        if (mb_strlen($question) > 500) {
            return $this->json(['error' => 'Question trop longue (500 caractères max).'], 400);
        }

        $user = $this->getUser();

        try {
            $response = $this->chatbotService->ask(
                $question,
                $history,
                $user instanceof User ? $user : null,
            );

            return $this->json(['response' => $response]);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Une erreur est survenue.'], 500);
        }
    }
}
