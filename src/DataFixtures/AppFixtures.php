<?php

namespace App\DataFixtures;

use App\Entity\AiLog;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Category;
use App\Entity\Favorite;
use App\Entity\Image;
use App\Entity\Order;
use App\Entity\Refund;
use App\Entity\Restaurant;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\StatusOrderEnum;
use App\Enum\StatusRefundEnum;
use App\Enum\StatusRestaurantEnum;
use App\Enum\StatusTicketEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    private const FIXTURE_PASSWORD = 'password';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $faker->seed(42);

        // -------------------------
        // USERS: 3 admins, 10 acheteurs, 5 vendeurs
        // -------------------------
        $admins = $this->createAdmins($manager, $faker);
        $buyers = $this->createBuyers($manager, $faker);
        $vendors = $this->createVendors($manager, $faker);

        // -------------------------
        // CATEGORIES: 5 cuisines
        // -------------------------
        $categories = $this->createCategories($manager, $faker);

        // -------------------------
        // RESTAURANTS: 30 (10 enchère passée, 15 à venir, 5 en cours)
        // -------------------------
        $restaurants = $this->createRestaurants($manager, $faker, $vendors, $categories);

        // -------------------------
        // CART + CART ITEMS (acheteurs)
        // -------------------------
        $this->createCartsAndCartItems($manager, $faker, $buyers, $restaurants);

        // -------------------------
        // ORDERS + TICKETS (acheteurs)
        // -------------------------
        $orders = $this->createOrdersAndTickets($manager, $faker, $buyers, $restaurants);

        // -------------------------
        // FAVORIS (acheteurs)
        // -------------------------
        $this->createFavorites($manager, $faker, $buyers, $restaurants);

        // -------------------------
        // REFUNDS
        // -------------------------
        $this->createRefunds($manager, $faker, $orders, $admins);

        // -------------------------
        // AI LOGS
        // -------------------------
        $this->createAiLogs($manager, $faker, $restaurants, $buyers, $vendors, $admins);

        $manager->flush();
    }

    /**
     * @return list<User>
     */
    private function createAdmins(ObjectManager $manager, \Faker\Generator $faker): array
    {
        $admins = [];
        $emails = ['admin@carte-blanche.fr', $faker->unique()->safeEmail(), $faker->unique()->safeEmail()];

        foreach ($emails as $i => $email) {
            $u = new User();
            $u->setEmail($email);
            $u->setFirstName(0 === $i ? 'Admin' : $faker->firstName());
            $u->setLastName(0 === $i ? 'CarteBlanche' : $faker->lastName());
            $u->setPhoneNumber($faker->optional(0.6)->phoneNumber());
            $u->setRoles(['ROLE_ADMIN']);
            $u->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 year', 'now')));
            $u->setUpdatedAt($u->getCreatedAt());
            $u->setIsVerified(true);
            $u->setIsSuspended(false);
            $u->setPassword($this->passwordHasher->hashPassword($u, self::FIXTURE_PASSWORD));
            $manager->persist($u);
            $admins[] = $u;
        }

        return $admins;
    }

    /**
     * @return list<User>
     */
    private function createBuyers(ObjectManager $manager, \Faker\Generator $faker): array
    {
        $buyers = [];
        $emails = ['buyer@carte-blanche.fr'];
        for ($i = 0; $i < 9; ++$i) {
            $emails[] = $faker->unique()->safeEmail();
        }

        foreach ($emails as $i => $email) {
            $u = new User();
            $u->setEmail($email);
            $u->setFirstName($faker->firstName());
            $u->setLastName($faker->lastName());
            $u->setPhoneNumber($faker->optional(0.8)->phoneNumber());
            $u->setRoles(['ROLE_USER']);
            $u->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 year', 'now')));
            $u->setUpdatedAt($u->getCreatedAt());
            $u->setIsVerified($faker->boolean(90));
            $u->setIsSuspended($faker->boolean(5));
            $u->setPassword($this->passwordHasher->hashPassword($u, self::FIXTURE_PASSWORD));
            $manager->persist($u);
            $buyers[] = $u;
        }

        return $buyers;
    }

    /**
     * @return list<User>
     */
    private function createVendors(ObjectManager $manager, \Faker\Generator $faker): array
    {
        $vendors = [];
        $emails = ['vendor@carte-blanche.fr'];
        for ($i = 0; $i < 4; ++$i) {
            $emails[] = $faker->unique()->safeEmail();
        }

        foreach ($emails as $i => $email) {
            $u = new User();
            $u->setEmail($email);
            $u->setFirstName($faker->firstName());
            $u->setLastName($faker->lastName());
            $u->setPhoneNumber($faker->optional(0.8)->phoneNumber());
            $u->setRoles(['ROLE_VENDOR']);
            $u->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 year', 'now')));
            $u->setUpdatedAt($u->getCreatedAt());
            $u->setIsVerified($faker->boolean(85));
            $u->setIsSuspended(false);
            $u->setPassword($this->passwordHasher->hashPassword($u, self::FIXTURE_PASSWORD));
            $manager->persist($u);
            $vendors[] = $u;
        }

        return $vendors;
    }

    /**
     * @return list<Category>
     */
    private function createCategories(ObjectManager $manager, \Faker\Generator $faker): array
    {
        $names = [
            'Français' => 'francais',
            'Italien' => 'italien',
            'Japonais' => 'japonais',
            'Mexicain' => 'mexicain',
            'Indien' => 'indien',
        ];
        $categories = [];
        foreach ($names as $name => $slug) {
            $c = new Category();
            $c->setName($name);
            $c->setSlug($slug);
            $c->setDescription($faker->optional(0.7)->sentence(10));
            $manager->persist($c);
            $categories[] = $c;
        }

        return $categories;
    }

    /**
     * @param list<User>     $vendors
     * @param list<Category> $categories
     *
     * @return list<Restaurant>
     */
    private function createRestaurants(
        ObjectManager $manager,
        \Faker\Generator $faker,
        array $vendors,
        array $categories,
    ): array {
        $cities = [
            ['Paris', 48.8566, 2.3522],
            ['Lyon', 45.7640, 4.8357],
            ['Marseille', 43.2965, 5.3698],
            ['Bordeaux', 44.8378, -0.5792],
            ['Toulouse', 43.6047, 1.4442],
        ];

        $restaurants = [];
        $now = new \DateTimeImmutable();

        // 10 avec enchère passée (TERMINEE ou VENDU)
        for ($i = 0; $i < 10; ++$i) {
            $restaurants[] = $this->createOneRestaurant(
                $manager,
                $faker,
                $vendors[$i % 5],
                $categories,
                $cities,
                $faker->randomElement([StatusRestaurantEnum::TERMINEE, StatusRestaurantEnum::VENDU]),
                $faker->dateTimeBetween('-6 months', '-1 week'),
                $i,
            );
        }

        // 15 avec enchère à venir (PROGRAMME)
        for ($i = 0; $i < 15; ++$i) {
            $restaurants[] = $this->createOneRestaurant(
                $manager,
                $faker,
                $vendors[$i % 5],
                $categories,
                $cities,
                StatusRestaurantEnum::PROGRAMME,
                $faker->dateTimeBetween('+1 week', '+6 months'),
                10 + $i,
            );
        }

        // 5 en cours (EN_COURS)
        for ($i = 0; $i < 5; ++$i) {
            $restaurants[] = $this->createOneRestaurant(
                $manager,
                $faker,
                $vendors[$i % 5],
                $categories,
                $cities,
                StatusRestaurantEnum::EN_COURS,
                $faker->dateTimeBetween('-1 week', '+3 days'),
                25 + $i,
            );
        }

        return $restaurants;
    }

    /**
     * @param list<Category>                                   $categories
     * @param array<int, array{0: string, 1: float, 2: float}> $cities
     */
    private function createOneRestaurant(
        ObjectManager $manager,
        \Faker\Generator $faker,
        User $owner,
        array $categories,
        array $cities,
        StatusRestaurantEnum $status,
        \DateTimeInterface $auctionDate,
        int $index = 0,
    ): Restaurant {
        [$city, $lat0, $lng0] = $faker->randomElement($cities);
        $lat = $lat0 + $faker->randomFloat(6, -0.03, 0.03);
        $lng = $lng0 + $faker->randomFloat(6, -0.05, 0.05);

        $askingPrice = $faker->numberBetween(80_000, 1_500_000);
        $ticketPrice = match (true) {
            $askingPrice < 100_000 => 50,
            $askingPrice < 300_000 => 100,
            $askingPrice < 500_000 => 200,
            default => 350,
        };

        $r = new Restaurant();
        $r->setName($faker->company().' - '.$faker->words(2, true));
        $r->setDescription($faker->optional(0.9)->paragraphs(2, true));
        $r->setAddress($faker->streetAddress().', '.$city);
        $r->setLatitude($lat);
        $r->setLongitude($lng);
        $r->setCapacity($faker->numberBetween(25, 120));
        $r->setAskingPrice(number_format((float) $askingPrice, 2, '.', ''));
        $r->setTicketPrice(number_format((float) $ticketPrice, 2, '.', ''));
        $r->setMaxCapacity($faker->numberBetween(30, 80));
        $r->setTicketsSold(0);
        $r->setViewCount($faker->numberBetween(0, 5000));
        $r->setFavoriteCount(0);
        $r->setOwner($owner);
        $r->setStatus($status);
        $r->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-10 months', 'now')));

        $auctionDt = $auctionDate instanceof \DateTime ? $auctionDate : \DateTime::createFromInterface($auctionDate);
        $r->setAuctionDate($auctionDt);
        $r->setAuctionTime(\DateTime::createFromFormat(
            'H:i:s',
            sprintf('%02d:%02d:00', $faker->numberBetween(9, 18), $faker->randomElement([0, 30])),
        ) ?: new \DateTime('12:00:00'));
        $r->setAuctionLocation($faker->streetAddress().', '.$city);
        $r->setAuctionLocationLat($lat0 + $faker->randomFloat(6, -0.02, 0.02));
        $r->setAuctionLocationLng($lng0 + $faker->randomFloat(6, -0.03, 0.03));

        if ($faker->boolean(70)) {
            $r->setAnnualRevenue(number_format($faker->randomFloat(2, 150_000, 2_000_000), 2, '.', ''));
        }
        if ($faker->boolean(60)) {
            $r->setRent(number_format($faker->randomFloat(2, 1000, 15_000), 2, '.', ''));
        }
        $r->setPappersUrl($faker->optional(0.3)->url());

        foreach ($faker->randomElements($categories, $faker->numberBetween(1, 3)) as $cat) {
            $r->addCategory($cat);
        }

        $manager->persist($r);

        // Images fictives
        $placeholders = ['restaurant-1.jpg', 'restaurant-2.jpg', 'restaurant-3.jpg', 'restaurant-4.jpg', 'restaurant-5.jpg'];
        $imgCount = $faker->numberBetween(1, 4);
        for ($p = 0; $p < $imgCount; ++$p) {
            $img = new Image();
            $img->setFileName($placeholders[($index + $p) % count($placeholders)]);
            $img->setPosition($p);
            $img->setRestaurant($r);
            $manager->persist($img);
        }

        return $r;
    }

    /**
     * @param list<User>       $buyers
     * @param list<Restaurant> $restaurants
     *
     * @return list<Order>
     */
    private function createOrdersAndTickets(
        ObjectManager $manager,
        \Faker\Generator $faker,
        array $buyers,
        array $restaurants,
    ): array {
        $orders = [];
        $restaurantsWithAuction = array_filter($restaurants, fn (Restaurant $r) => null !== $r->getAuctionDate());
        if ([] === $restaurantsWithAuction) {
            return $orders;
        }

        for ($i = 0; $i < 40; ++$i) {
            $buyer = $faker->randomElement($buyers);
            $restaurant = $faker->randomElement($restaurantsWithAuction);
            $qty = $faker->numberBetween(1, 3);
            $unitPrice = (float) ($restaurant->getTicketPrice() ?? 100);
            $total = $unitPrice * $qty;

            $order = new Order();
            $order->setBuyer($buyer);
            $order->setReference('CB-'.$faker->unique()->numerify('##########'));
            $order->setTotalAmount(number_format($total, 2, '.', ''));
            $order->setStatus($faker->randomElement([StatusOrderEnum::EN_ATTENTE, StatusOrderEnum::PAYEE]));
            $order->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-4 months', 'now')));
            $manager->persist($order);
            $orders[] = $order;

            for ($t = 0; $t < $qty; ++$t) {
                $ticket = new Ticket();
                $ticket->setQrCode($faker->unique()->uuid());
                $ticket->setStatus($faker->randomElement([StatusTicketEnum::VALIDE, StatusTicketEnum::UTILISE]));
                $ticket->setCreatedAt($order->getCreatedAt());
                $ticket->setRestaurant($restaurant);
                $ticket->setOrder($order);
                $manager->persist($ticket);
            }

            $restaurant->setTicketsSold($restaurant->getTicketsSold() + $qty);
        }

        return $orders;
    }

    /**
     * @param list<User>       $buyers
     * @param list<Restaurant> $restaurants
     */
    private function createCartsAndCartItems(
        ObjectManager $manager,
        \Faker\Generator $faker,
        array $buyers,
        array $restaurants,
    ): void {
        foreach ($buyers as $buyer) {
            if (!$faker->boolean(70)) {
                continue;
            }

            $cart = new Cart();
            $cart->setUser($buyer);
            $cart->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-3 months', 'now')));
            if ($faker->boolean(50)) {
                $cart->setUpdatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 month', 'now')));
            } else {
                $cart->setUpdatedAt(null);
            }
            $manager->persist($cart);

            $itemsCount = $faker->numberBetween(1, 3);
            $pickedRestaurants = $faker->randomElements($restaurants, min($itemsCount, count($restaurants)));

            foreach ($pickedRestaurants as $rest) {
                $item = new CartItem();
                $item->setCart($cart);
                $item->setRestaurant($rest);
                $item->setQuantity($faker->numberBetween(1, 3));
                $manager->persist($item);
            }
        }
    }

    /**
     * @param list<Order> $orders
     * @param list<User>  $admins
     */
    private function createRefunds(
        ObjectManager $manager,
        \Faker\Generator $faker,
        array $orders,
        array $admins,
    ): void {
        if ([] === $orders) {
            return;
        }

        $refundStatuses = [
            StatusRefundEnum::EN_ATTENTE,
            StatusRefundEnum::APPROUVE,
            StatusRefundEnum::REFUSE,
            StatusRefundEnum::TRAITE,
        ];

        for ($i = 0; $i < min(30, (int) (count($orders) * 0.8)); ++$i) {
            $order = $faker->randomElement($orders);

            $refund = new Refund();
            $refund->setOrder($order);
            $refund->setRequestedBy($order->getBuyer());
            $refund->setProcessedBy($faker->optional(0.6)->randomElement($admins));
            $refund->setAmount(number_format($faker->randomFloat(2, 20, 500), 2, '.', ''));
            $refund->setReasonRefund($faker->sentence(14));
            $refund->setStripeRefundId($faker->optional(0.6)->uuid());
            $refund->setStatus($faker->randomElement($refundStatuses));
            $refund->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-4 months', 'now')));
            $manager->persist($refund);
        }
    }

    /**
     * @param list<Restaurant> $restaurants
     * @param list<User>       $buyers
     * @param list<User>       $vendors
     * @param list<User>       $admins
     */
    private function createAiLogs(
        ObjectManager $manager,
        \Faker\Generator $faker,
        array $restaurants,
        array $buyers,
        array $vendors,
        array $admins,
    ): void {
        $aiTypes = ['description', 'recommendation', 'chatbot', 'email_personalization'];
        $aiModels = ['mistral:7b', 'llama3.1:8b'];
        $allUsers = array_merge($buyers, $vendors, $admins);

        for ($i = 0; $i < 200; ++$i) {
            $log = new AiLog();

            $type = $faker->randomElement($aiTypes);
            $model = $faker->randomElement($aiModels);

            $prompt = match ($type) {
                'description' => 'Rédige une description attractive et réaliste pour ce restaurant.',
                'recommendation' => 'Recommande 3 axes d\'amélioration pour augmenter la valeur perçue avant l\'enchère.',
                'chatbot' => 'Réponds à une question d\'investisseur sur le bail et le CA.',
                'email_personalization' => 'Personnalise un email de relance suite à une visite d\'annonce.',
                default => 'Instruction IA générique.',
            };

            $log->setType($type);
            $log->setModel($model);
            $log->setPrompt($prompt.' Contexte: '.$faker->sentence(18));
            $log->setResponse($faker->paragraphs(2, true));
            $log->setToken($faker->optional(0.8)->numberBetween(120, 2200));
            if ($faker->boolean(80)) {
                $log->setDuration(number_format($faker->randomFloat(3, 0.12, 8.5), 3, '.', ''));
            } else {
                $log->setDuration(null);
            }
            $log->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-6 months', 'now')));
            $log->setRestaurant($faker->optional(0.7)->randomElement($restaurants));
            $log->setUser($faker->optional(0.6)->randomElement($allUsers));

            $manager->persist($log);
        }
    }

    /**
     * @param list<User>       $buyers
     * @param list<Restaurant> $restaurants
     */
    private function createFavorites(
        ObjectManager $manager,
        \Faker\Generator $faker,
        array $buyers,
        array $restaurants,
    ): void {
        $pairs = [];
        $maxFavorites = min(80, count($buyers) * count($restaurants) / 2);

        for ($i = 0; $i < $maxFavorites; ++$i) {
            $user = $faker->randomElement($buyers);
            $restaurant = $faker->randomElement($restaurants);
            $key = spl_object_id($user).'-'.spl_object_id($restaurant);
            if (isset($pairs[$key])) {
                continue;
            }
            $pairs[$key] = true;

            $fav = new Favorite();
            $fav->setUser($user);
            $fav->setRestaurant($restaurant);
            $dt = \DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-8 months', 'now'));
            $fav->setCreatedAt($dt);
            $fav->setUpdatedAt($dt);
            $manager->persist($fav);
            $restaurant->setFavoriteCount($restaurant->getFavoriteCount() + 1);
        }
    }
}
