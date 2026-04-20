<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\StatusRestaurantEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed',
    description: 'Crée des données de démonstration en production (admin, vendor, buyer, restaurants).',
)]
class SeedCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Confirme l\'exécution sans prompt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('force')) {
            $io->warning('Cette commande insère des données de démo. Utilisez --force pour confirmer.');

            return Command::FAILURE;
        }

        $io->title('Seeding base de données Carte Blanche');

        // ── Vérification : éviter les doublons ─────────────────
        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => 'admin@carte-blanche.fr']);
        if ($existing) {
            $io->warning('Les données de démo existent déjà (admin@carte-blanche.fr trouvé). Abandon.');

            return Command::SUCCESS;
        }

        // ── Catégories ─────────────────────────────────────────
        $categories = [];
        foreach (
            [
                'Français' => 'francais',
                'Italien' => 'italien',
                'Japonais' => 'japonais',
                'Mexicain' => 'mexicain',
                'Indien' => 'indien',
            ] as $name => $slug
        ) {
            $cat = new Category();
            $cat->setName($name)->setSlug($slug);
            $this->em->persist($cat);
            $categories[] = $cat;
        }

        // ── Utilisateurs ───────────────────────────────────────
        $admin = $this->createUser('admin@carte-blanche.fr', 'Admin', 'CarteBlanche', ['ROLE_ADMIN'], 'Admin1234!');
        $vendor = $this->createUser('vendor@carte-blanche.fr', 'Pierre', 'Vendeur', ['ROLE_VENDOR'], 'Vendor1234!');
        $buyer = $this->createUser('buyer@carte-blanche.fr', 'Marie', 'Acheteuse', ['ROLE_USER'], 'Buyer1234!');

        $io->success('Utilisateurs créés : admin / vendor / buyer');

        // ── Restaurants ────────────────────────────────────────
        $restaurants = [
            [
                'name' => 'Le Petit Bistrot Parisien',
                'address' => '12 Rue de Rivoli, Paris',
                'lat' => 48.8566,
                'lng' => 2.3522,
                'price' => '250000.00',
                'ticket' => '200.00',
                'status' => StatusRestaurantEnum::PROGRAMME,
                'auctionDays' => '+14 days',
                'categories' => [0],
                'description' => 'Bistrot parisien authentique avec une clientèle fidèle depuis 15 ans. Emplacement premium près du Louvre.',
            ],
            [
                'name' => 'Trattoria Bella Napoli',
                'address' => '8 Rue de la Paix, Lyon',
                'lat' => 45.7640,
                'lng' => 4.8357,
                'price' => '180000.00',
                'ticket' => '100.00',
                'status' => StatusRestaurantEnum::PROGRAMME,
                'auctionDays' => '+21 days',
                'categories' => [1],
                'description' => 'Restaurant italien authentique avec four à bois traditionnel. Cuisine napolitaine reconnue dans tout Lyon.',
            ],
            [
                'name' => 'Sakura Sushi Bar',
                'address' => '45 Avenue du Prado, Marseille',
                'lat' => 43.2965,
                'lng' => 5.3698,
                'price' => '320000.00',
                'ticket' => '200.00',
                'status' => StatusRestaurantEnum::EN_COURS,
                'auctionDays' => '+3 days',
                'categories' => [2],
                'description' => 'Sushi bar haut de gamme avec chef japonais expérimenté. Concept unique à Marseille avec vue sur la mer.',
            ],
            [
                'name' => 'Casa Mexico',
                'address' => '22 Rue Sainte-Catherine, Bordeaux',
                'lat' => 44.8378,
                'lng' => -0.5792,
                'price' => '120000.00',
                'ticket' => '100.00',
                'status' => StatusRestaurantEnum::PROGRAMME,
                'auctionDays' => '+30 days',
                'categories' => [3],
                'description' => 'Restaurant mexicain festif au cœur de Bordeaux. Terrasse animée et carte de cocktails premium.',
            ],
            [
                'name' => 'Spices of India',
                'address' => '67 Rue du Taur, Toulouse',
                'lat' => 43.6047,
                'lng' => 1.4442,
                'price' => '95000.00',
                'ticket' => '50.00',
                'status' => StatusRestaurantEnum::TERMINEE,
                'auctionDays' => '-7 days',
                'categories' => [4],
                'description' => 'Restaurant indien familial avec recettes traditionnelles du Rajasthan. 12 ans d\'expérience à Toulouse.',
            ],
            [
                'name' => 'Brasserie du Marché',
                'address' => '3 Place du Marché, Paris',
                'lat' => 48.8600,
                'lng' => 2.3400,
                'price' => '450000.00',
                'ticket' => '350.00',
                'status' => StatusRestaurantEnum::PROGRAMME,
                'auctionDays' => '+45 days',
                'categories' => [0],
                'description' => 'Grande brasserie parisienne avec 120 couverts, cave à vins exceptionnelle et cuisine gastronomique.',
            ],
        ];

        foreach ($restaurants as $data) {
            $r = new Restaurant();
            $r->setName($data['name'])
                ->setAddress($data['address'])
                ->setLatitude($data['lat'])
                ->setLongitude($data['lng'])
                ->setAskingPrice($data['price'])
                ->setTicketPrice($data['ticket'])
                ->setCapacity(60)
                ->setMaxCapacity(50)
                ->setTicketsSold(0)
                ->setViewCount(rand(100, 2000))
                ->setFavoriteCount(0)
                ->setStatus($data['status'])
                ->setOwner($vendor)
                ->setDescription($data['description'])
                ->setAuctionLocation($data['address'])
                ->setAuctionLocationLat($data['lat'])
                ->setAuctionLocationLng($data['lng'])
                ->setAnnualRevenue(number_format((float) $data['price'] * 1.5, 2, '.', ''))
                ->setCreatedAt(new \DateTimeImmutable('-30 days'));

            $auctionDate = new \DateTime($data['auctionDays']);
            $r->setAuctionDate($auctionDate);
            $r->setAuctionTime(new \DateTime('14:00:00'));

            foreach ($data['categories'] as $catIndex) {
                $r->addCategory($categories[$catIndex]);
            }

            $this->em->persist($r);
        }

        $io->success(count($restaurants).' restaurants créés');

        $this->em->flush();

        $io->success('Seed terminé avec succès !');
        $io->table(
            ['Rôle', 'Email', 'Mot de passe'],
            [
                ['Admin', 'admin@carte-blanche.fr', 'Admin1234!'],
                ['Vendeur', 'vendor@carte-blanche.fr', 'Vendor1234!'],
                ['Acheteur', 'buyer@carte-blanche.fr', 'Buyer1234!'],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(string $email, string $first, string $last, array $roles, string $password): User
    {
        $now = new \DateTimeImmutable();
        $user = new User();
        $user->setEmail($email)
            ->setFirstName($first)
            ->setLastName($last)
            ->setRoles($roles)
            ->setIsVerified(true)
            ->setIsSuspended(false)
            ->setCreatedAt($now)
            ->setUpdatedAt($now)
            ->setPassword($this->hasher->hashPassword($user, $password));

        $this->em->persist($user);

        return $user;
    }
}
