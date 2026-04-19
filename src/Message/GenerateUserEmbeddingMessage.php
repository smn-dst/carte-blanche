<?php

namespace App\Message;

final class GenerateUserEmbeddingMessage
{
    public function __construct(
        public readonly int $userId,
    ) {
    }
}
