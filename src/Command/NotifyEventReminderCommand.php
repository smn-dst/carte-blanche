<?php

namespace App\Command;

use App\Message\SendEventReminderEmailMessage;
use App\Repository\TicketRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:notify:event-reminder',
    description: 'Envoie un email de rappel aux utilisateurs dont un événement a lieu dans 7 jours.',
)]
class NotifyEventReminderCommand extends Command
{
    public function __construct(
        private readonly TicketRepository $ticketRepository,
        private readonly MessageBusInterface $bus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Fenêtre de ±1h autour de J+7 pour couvrir les décalages d'exécution du cron
        $from = new \DateTimeImmutable('+6 days 23 hours');
        $to = new \DateTimeImmutable('+7 days 1 hour');

        $tickets = $this->ticketRepository->findTicketsForUpcomingEvents($from, $to);

        if (0 === count($tickets)) {
            $io->info('Aucun ticket d\'événement à rappeler dans 7 jours.');

            return Command::SUCCESS;
        }

        $dispatched = 0;
        foreach ($tickets as $ticket) {
            $this->bus->dispatch(new SendEventReminderEmailMessage($ticket->getId()));
            ++$dispatched;
        }

        $io->success(sprintf('%d email(s) de rappel d\'événement dispatchés.', $dispatched));

        return Command::SUCCESS;
    }
}
