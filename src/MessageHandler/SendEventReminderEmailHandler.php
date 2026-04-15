<?php

namespace App\MessageHandler;

use App\Enum\StatusTicketEnum;
use App\Message\SendEventReminderEmailMessage;
use App\Repository\TicketRepository;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class SendEventReminderEmailHandler
{
    public function __construct(
        private TicketRepository $ticketRepository,
        private SendMailService $sendMailService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(SendEventReminderEmailMessage $message): void
    {
        $ticket = $this->ticketRepository->find($message->ticketId);

        if (null === $ticket) {
            return;
        }

        if (StatusTicketEnum::VALIDE !== $ticket->getStatus()) {
            return;
        }

        $user = $ticket->getOrder()?->getBuyer();
        if (null === $user || null === $user->getEmail()) {
            return;
        }

        $this->sendMailService->sendEventReminderEmail($user, $ticket);

        $ticket->setReminderEmailSentAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }
}
