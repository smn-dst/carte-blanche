<?php

namespace App\MessageHandler;

use App\Entity\User;
use App\Message\GenerateUserEmbeddingMessage;
use App\Service\EmbeddingService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GenerateUserEmbeddingHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EmbeddingService $embeddingService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(GenerateUserEmbeddingMessage $message): void
    {
        $user = $this->em->getRepository(User::class)->find($message->userId);

        if (!$user instanceof User) {
            $this->logger->warning('GenerateUserEmbeddingHandler: user {id} introuvable.', [
                'id' => $message->userId,
            ]);

            return;
        }

        $embedding = $user->getUserPreferenceEmbedding();
        if (null === $embedding) {
            $this->logger->info('GenerateUserEmbeddingHandler: pas de préférences pour user {id}.', [
                'id' => $message->userId,
            ]);

            return;
        }

        $this->embeddingService->embedUserPreferences($embedding);

        $this->logger->info('GenerateUserEmbeddingHandler: embedding généré pour user {id}.', [
            'id' => $message->userId,
        ]);
    }
}
