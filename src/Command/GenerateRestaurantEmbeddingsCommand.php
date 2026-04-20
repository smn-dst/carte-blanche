<?php

namespace App\Command;

use App\Enum\StatusRestaurantEnum;
use App\Repository\RestaurantEmbeddingRepository;
use App\Repository\RestaurantRepository;
use App\Service\EmbeddingService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-restaurant-embeddings',
    description: 'Génère les embeddings vectoriels pour tous les restaurants publiés/programmés.',
)]
class GenerateRestaurantEmbeddingsCommand extends Command
{
    public function __construct(
        private readonly RestaurantRepository $restaurantRepository,
        private readonly RestaurantEmbeddingRepository $embeddingRepository,
        private readonly EmbeddingService $embeddingService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Régénère les embeddings même s\'ils existent déjà.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');

        $statuses = [StatusRestaurantEnum::PUBLIE, StatusRestaurantEnum::PROGRAMME];
        $restaurants = $this->restaurantRepository->findBy(['status' => $statuses]);

        if (empty($restaurants)) {
            $io->warning('Aucun restaurant publié ou programmé trouvé.');

            return Command::SUCCESS;
        }

        $io->title(sprintf('Génération des embeddings pour %d restaurant(s)', count($restaurants)));

        $progressBar = new ProgressBar($output, count($restaurants));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $progressBar->start();

        $skipped = 0;
        $generated = 0;
        $errors = 0;

        foreach ($restaurants as $restaurant) {
            $progressBar->setMessage((string) $restaurant->getName());

            if (!$force) {
                $existing = $this->embeddingRepository->findOneBy(['restaurant' => $restaurant]);
                if ($existing && !empty($existing->getEmbedding())) {
                    ++$skipped;
                    $progressBar->advance();
                    continue;
                }
            }

            try {
                $this->embeddingService->embedRestaurant($restaurant);
                ++$generated;
            } catch (\Throwable $e) {
                ++$errors;
                $io->newLine();
                $io->error(sprintf(
                    'Erreur sur "%s" (id=%d) : %s',
                    $restaurant->getName(),
                    (int) $restaurant->getId(),
                    $e->getMessage(),
                ));
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        $io->success(sprintf(
            'Terminé : %d générés, %d ignorés (déjà existants), %d erreurs.',
            $generated,
            $skipped,
            $errors,
        ));

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
