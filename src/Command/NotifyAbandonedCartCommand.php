<?php

namespace App\Command;

use App\Message\SendAbandonedCartEmailMessage;
use App\Repository\CartRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:notify:abandoned-cart',
    description: 'Envoie un email de rappel aux utilisateurs ayant abandonné leur panier depuis plus de 24h.',
)]
class NotifyAbandonedCartCommand extends Command
{
    public function __construct(
        private readonly CartRepository $cartRepository,
        private readonly MessageBusInterface $bus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $abandonedBefore = new \DateTimeImmutable('-24 hours');
        $carts = $this->cartRepository->findAbandonedCarts($abandonedBefore);

        if (0 === count($carts)) {
            $io->info('Aucun panier abandonné trouvé.');

            return Command::SUCCESS;
        }

        $dispatched = 0;
        foreach ($carts as $cart) {
            $this->bus->dispatch(new SendAbandonedCartEmailMessage($cart->getId()));
            ++$dispatched;
        }

        $io->success(sprintf('%d email(s) d\'abandon de panier dispatchés.', $dispatched));

        return Command::SUCCESS;
    }
}
