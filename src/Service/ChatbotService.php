<?php

namespace App\Service;

use App\Entity\AiLog;
use App\Entity\FaqEntry;
use App\Entity\User;
use App\Neuron\ChatbotAgent;
use Doctrine\ORM\EntityManagerInterface;
use NeuronAI\Chat\Messages\UserMessage;
use Psr\Log\LoggerInterface;

class ChatbotService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<array{role: string, content: string}> $history
     */
    public function ask(string $question, array $history = [], ?User $user = null): string
    {
        $start = microtime(true);

        $context = $this->buildContext($question);
        $prompt = $this->buildPrompt($question, $context);

        try {
            // Pas de tools NeuronAI : le contexte FAQ est déjà injecté dans le prompt
            $agent = ChatbotAgent::make();

            $message = $agent->chat(new UserMessage($prompt))->getMessage();
            $response = trim((string) $message->getContent());
        } catch (\Throwable $e) {
            $this->logger->error('ChatbotService: erreur LLM: {message}', [
                'message' => $e->getMessage(),
            ]);
            $response = 'Désolé, je rencontre une difficulté technique. Réessaie dans quelques instants !';
        }

        $duration = round(microtime(true) - $start, 3);
        $this->logInteraction($question, $response, $duration, $user);

        return $response;
    }

    private function buildContext(string $question): string
    {
        $keywords = $this->extractKeywords($question);

        if (empty($keywords)) {
            return '';
        }

        $qb = $this->em->createQueryBuilder()
            ->select('f')
            ->from(FaqEntry::class, 'f');

        $conditions = [];
        foreach ($keywords as $i => $keyword) {
            $conditions[] = "(LOWER(f.question) LIKE LOWER(:kw{$i}) OR LOWER(f.answer) LIKE LOWER(:kw{$i}))";
            $qb->setParameter("kw{$i}", '%'.$keyword.'%');
        }

        $qb->where(implode(' OR ', $conditions))->setMaxResults(3);

        /** @var FaqEntry[] $entries */
        $entries = $qb->getQuery()->getResult();

        if (empty($entries)) {
            return '';
        }

        $parts = [];
        foreach ($entries as $entry) {
            $parts[] = 'Q: '.$entry->getQuestion()."\nR: ".$entry->getAnswer();
        }

        return implode("\n\n", $parts);
    }

    private function buildPrompt(string $question, string $context): string
    {
        if ('' === $context) {
            return $question;
        }

        return <<<PROMPT
            Contexte issu de notre FAQ (utilise-le si pertinent) :
            ---
            {$context}
            ---

            Question de l'utilisateur : {$question}
            PROMPT;
    }

    /**
     * @return string[]
     */
    private function extractKeywords(string $question): array
    {
        $stopWords = [
            'est',
            'une',
            'des',
            'les',
            'pour',
            'dans',
            'sur',
            'avec',
            'que',
            'qui',
            'quoi',
            'comment',
            'combien',
            'quand',
            'où',
            'quel',
            'quelle',
            'mon',
            'ton',
            'son',
            'notre',
            'votre',
            'leur',
            'pas',
            'plus',
            'très',
            'bien',
            'avoir',
            'être',
            'fait',
            'faire',
            'peut',
            'faut',
        ];

        $words = preg_split('/\s+/', mb_strtolower(trim($question))) ?: [];

        return array_values(array_filter($words, static function (string $word) use ($stopWords): bool {
            $word = preg_replace('/[^a-z0-9àâäéèêëîïôùûüç]/u', '', $word) ?? '';

            return strlen($word) > 3 && !in_array($word, $stopWords, true);
        }));
    }

    private function logInteraction(string $question, string $response, float $duration, ?User $user): void
    {
        try {
            $model = $_ENV['GROQ_CHATBOT_MODEL'] ?? $_SERVER['GROQ_CHATBOT_MODEL'] ?? 'llama-3.3-70b-versatile';

            $log = new AiLog();
            $log->setPrompt($question);
            $log->setResponse($response);
            $log->setModel($model);
            $log->setType('chatbot');
            $log->setDuration((string) $duration);
            $log->setCreatedAt(new \DateTimeImmutable());

            if ($user instanceof User) {
                $log->setUser($user);
            }

            $this->em->persist($log);
            $this->em->flush();
        } catch (\Throwable $e) {
            $this->logger->error('ChatbotService: échec log: {message}', ['message' => $e->getMessage()]);
        }
    }
}
