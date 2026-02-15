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
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $faker->seed(42);

        // -------------------------
        // USERS
        // -------------------------
        $admins = [];
        $owners = [];
        $buyers = [];

        // Admin
        $admin = new User();
        $admin->setEmail('admin@test.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('CarteBlanche');
        $admin->setPhoneNumber($faker->phoneNumber());
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setCreatedAt(new \DateTimeImmutable('now'));
        $admin->setIsVerified(true);
        $admin->setIsSuspended(false);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin1234'));
        $manager->persist($admin);
        $admins[] = $admin;

        // Owners (ROLE_SELLER)
        for ($i = 0; $i < 20; ++$i) {
            $u = new User();
            $u->setEmail($faker->unique()->safeEmail());
            $u->setFirstName($faker->firstName());
            $u->setLastName($faker->lastName());
            $u->setPhoneNumber($faker->optional(0.75)->phoneNumber());
            $u->setRoles(['ROLE_SELLER']);
            $u->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-18 months', 'now')));
            $u->setIsVerified($faker->boolean(80));
            $u->setIsSuspended($faker->boolean(5));
            $u->setPassword($this->passwordHasher->hashPassword($u, 'password'));
            $manager->persist($u);
            $owners[] = $u;
        }

        // Buyers (ROLE_BUYER)
        for ($i = 0; $i < 80; ++$i) {
            $u = new User();
            $u->setEmail($faker->unique()->safeEmail());
            $u->setFirstName($faker->firstName());
            $u->setLastName($faker->lastName());
            $u->setPhoneNumber($faker->optional(0.85)->phoneNumber());
            $u->setRoles(['ROLE_BUYER']);
            $u->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-18 months', 'now')));
            $u->setIsVerified($faker->boolean(85));
            $u->setIsSuspended($faker->boolean(3));
            $u->setPassword($this->passwordHasher->hashPassword($u, 'password'));
            $manager->persist($u);
            $buyers[] = $u;
        }

        // -------------------------
        // CATEGORIES
        // -------------------------
        $categoryNames = [
            'Bistrot',
            'Brasserie',
            'Gastronomique',
            'Italien',
            'Japonais',
            'Libanais',
            'Crêperie',
            'Pizzeria',
            'Street-food',
            'Vegan',
            'Bar à vin',
            'Café',
            'Restaurant de quartier',
        ];

        $categories = [];
        foreach ($categoryNames as $name) {
            $c = new Category();
            $c->setName($name);
            $c->setSlug($this->slugify($name));
            $c->setDescription($faker->optional(0.6)->sentence(12));
            $manager->persist($c);
            $categories[] = $c;
        }

        // -------------------------
        // RESTAURANTS (Annonce + Enchère)
        // -------------------------
        $restaurantStatuses = [
            StatusRestaurantEnum::BROUILLON,
            StatusRestaurantEnum::EN_MODERATION,
            StatusRestaurantEnum::PUBLIE,
            StatusRestaurantEnum::EN_PAUSE,
            StatusRestaurantEnum::PROGRAMME,
            StatusRestaurantEnum::EN_COURS,
            StatusRestaurantEnum::TERMINEE,
            StatusRestaurantEnum::VENDU,
            StatusRestaurantEnum::ANNULE,
        ];

        $cities = [
            ['Paris', 48.8566, 2.3522],
            ['Lyon', 45.7640, 4.8357],
            ['Marseille', 43.2965, 5.3698],
            ['Bordeaux', 44.8378, -0.5792],
            ['Lille', 50.6292, 3.0573],
            ['Nantes', 47.2184, -1.5536],
            ['Toulouse', 43.6047, 1.4442],
            ['Nice', 43.7102, 7.2620],
        ];

        $restaurants = [];
        for ($i = 0; $i < 120; ++$i) {
            $owner = $faker->randomElement($owners);
            [$city, $lat0, $lng0] = $faker->randomElement($cities);

            $lat = $lat0 + $faker->randomFloat(6, -0.05, 0.05);
            $lng = $lng0 + $faker->randomFloat(6, -0.08, 0.08);

            $askingPrice = $faker->numberBetween(80_000, 2_500_000);

            $ticketPrice = match (true) {
                $askingPrice < 100_000 => 50,
                $askingPrice < 300_000 => 100,
                $askingPrice < 500_000 => 200,
                default => 350,
            };

            $r = new Restaurant();
            $r->setName($faker->company().' - '.$faker->words(2, true));
            $r->setDescription($faker->optional(0.85)->paragraphs(3, true));
            $r->setAddress($faker->streetAddress().', '.$city);
            $r->setLatitude($lat);
            $r->setLongitude($lng);
            $r->setCapacity($faker->numberBetween(20, 180));
            if ($faker->boolean(70)) {
                $annualRevenue = $faker->randomFloat(2, 150_000, 4_000_000);
                $r->setAnnualRevenue(number_format($annualRevenue, 2, '.', ''));
            } else {
                $r->setAnnualRevenue(null);
            }

            if ($faker->boolean(75)) {
                $rent = $faker->randomFloat(2, 800, 25_000);
                $r->setRent(number_format($rent, 2, '.', ''));
            } else {
                $r->setRent(null);
            }
            $r->setAskingPrice(number_format((float) $askingPrice, 2, '.', ''));
            $r->setTicketPrice($faker->boolean(70) ? number_format((float) $ticketPrice, 2, '.', '') : null);
            $r->setPappersUrl($faker->optional(0.4)->url());
            $r->setViewCount($faker->numberBetween(0, 12_000));
            $r->setFavoriteCount(0);
            $createdAt = \DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-10 months', 'now'));
            $r->setCreatedAt($createdAt);

            if ($faker->boolean(35)) {
                $updatedMutable = $faker->dateTimeBetween($createdAt->format('Y-m-d H:i:s'), 'now');
                $r->setUpdatedAt(\DateTimeImmutable::createFromMutable($updatedMutable));
            } else {
                $r->setUpdatedAt(null);
            }

            if ($faker->boolean(70)) {
                $auctionDT = $faker->dateTimeBetween('+3 days', '+90 days');
                $r->setAuctionDate($auctionDT);
                $r->setAuctionTime($auctionDT);
                $r->setAuctionLocation($faker->optional(0.8)->streetAddress().', '.$city);
                $r->setAuctionLocationLat($lat0 + $faker->randomFloat(6, -0.03, 0.03));
                $r->setAuctionLocationLng($lng0 + $faker->randomFloat(6, -0.05, 0.05));
                $r->setMaxCapacity($faker->numberBetween(30, 120));
                $r->setTicketsSold(0);
            } else {
                $r->setMaxCapacity(50);
                $r->setTicketsSold(0);
            }

            $r->setStatus($faker->randomElement($restaurantStatuses));
            $r->setOwner($owner);

            // ManyToMany Categories
            $picked = $faker->randomElements($categories, $faker->numberBetween(1, 3));
            foreach ($picked as $cat) {
                $r->addCategory($cat);
            }

            $manager->persist($r);
            $restaurants[] = $r;

            // Images
            $imgCount = $faker->numberBetween(1, 6);
            for ($p = 0; $p < $imgCount; ++$p) {
                $img = new Image();
                $img->setFilename($faker->uuid().'.jpg');
                $img->setPosition($p);
                $img->setRestaurant($r);
                $manager->persist($img);
            }
        }

        // -------------------------
        // CART (0..1 par user) + CART ITEMS
        // -------------------------
        $carts = [];
        foreach ($buyers as $buyer) {
            if (!$faker->boolean(70)) {
                continue;
            }

            $cart = new Cart();
            $cart->setUser($buyer);
            $cart->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-3 months', 'now')));
            if ($faker->boolean(50)) {
                $updated = $faker->dateTimeBetween('-1 month', 'now');
                $cart->setUpdatedAt(\DateTimeImmutable::createFromMutable($updated));
            } else {
                $cart->setUpdatedAt(null);
            }

            $manager->persist($cart);
            $carts[] = $cart;

            $itemsCount = $faker->numberBetween(1, 3);
            $pickedRestaurants = $faker->randomElements($restaurants, $itemsCount);

            foreach ($pickedRestaurants as $rest) {
                $item = new CartItem();
                $item->setCart($cart);
                $item->setRestaurant($rest);
                $item->setQuantity($faker->numberBetween(1, 3));
                $manager->persist($item);
            }
        }

        // -------------------------
        // ORDERS + TICKETS
        // -------------------------
        $orders = [];
        $orderStatuses = [
            StatusOrderEnum::EN_ATTENTE,
            StatusOrderEnum::PAYEE,
            StatusOrderEnum::REMBOURSEMENT_PARTIEL,
            StatusOrderEnum::REMBOURSEE,
            StatusOrderEnum::ECHOUEE,
        ];

        $ticketStatuses = [
            StatusTicketEnum::VALIDE,
            StatusTicketEnum::UTILISE,
            StatusTicketEnum::EXPIRE,
        ];

        for ($i = 0; $i < 160; ++$i) {
            $buyer = $faker->randomElement($buyers);
            $restaurant = $faker->randomElement($restaurants);

            $qty = $faker->numberBetween(1, 4);

            // ticket price: si restaurant null, fallback règle métier
            $asking = (int) $restaurant->getAskingPrice();
            $fallbackTicketPrice = match (true) {
                $asking < 100_000 => 50,
                $asking < 300_000 => 100,
                $asking < 500_000 => 200,
                default => 350,
            };

            $unit = (float) ($restaurant->getTicketPrice() ?? $fallbackTicketPrice);

            $order = new Order();
            $order->setBuyer($buyer);
            $order->setReference('CB-'.$faker->unique()->bothify('##########'));
            $order->setTotalAmount(number_format($unit * $qty, 2, '.', ''));
            $order->setStripeSessionId($faker->optional(0.6)->uuid());
            $order->setStripePaymentIntentId($faker->optional(0.6)->uuid());
            $order->setStatus($faker->randomElement($orderStatuses));
            $order->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-6 months', 'now')));

            $manager->persist($order);
            $orders[] = $order;

            for ($t = 0; $t < $qty; ++$t) {
                $ticket = new Ticket();
                $ticket->setQrCode($faker->unique()->uuid());
                $ticket->setStatus($faker->randomElement($ticketStatuses));
                $ticket->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-6 months', 'now')));
                $ticket->setRestaurant($restaurant);
                $ticket->setOrder($order);
                $manager->persist($ticket);
            }

            // MAJ tickets_sold
            $restaurant->setTicketsSold($restaurant->getTicketsSold() + $qty);
        }

        // -------------------------
        // FAVORITES (unique user+restaurant)
        // -------------------------
        $favoritePairs = [];

        for ($i = 0; $i < 600; ++$i) {
            $u = $faker->randomElement($buyers);
            $r = $faker->randomElement($restaurants);

            $key = spl_object_id($u).'-'.spl_object_id($r);
            if (isset($favoritePairs[$key])) {
                continue;
            }
            $favoritePairs[$key] = true;

            $fav = new Favorite();
            $fav->setUser($u);
            $fav->setRestaurant($r);
            $dt = \DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-10 months', 'now'));
            $fav->setCreatedAt($dt);
            $fav->setUpdatedAt($dt);
            $manager->persist($fav);

            $r->setFavoriteCount($r->getFavoriteCount() + 1);
        }

        // -------------------------
        // REFUNDS
        // -------------------------
        $refundStatuses = [
            StatusRefundEnum::EN_ATTENTE,
            StatusRefundEnum::APPROUVE,
            StatusRefundEnum::REFUSE,
            StatusRefundEnum::TRAITE,
        ];

        for ($i = 0; $i < 30; ++$i) {
            $order = $faker->randomElement($orders);

            $refund = new Refund();
            $refund->setOrder($order);
            $refund->setRequestedBy($order->getBuyer());
            $refund->setProcessedBy($faker->optional(0.6)->randomElement($admins)); // nullable
            $amount = $faker->randomFloat(2, 20, 500);
            $refund->setAmount(number_format($amount, 2, '.', ''));
            $refund->setReasonRefund($faker->sentence(14));
            $refund->setStripeRefundId($faker->optional(0.6)->uuid());
            $refund->setStatus($faker->randomElement($refundStatuses));
            $refund->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-4 months', 'now')));
            $manager->persist($refund);
        }

        // -------------------------
        // AI LOGS
        // -------------------------
        $aiTypes = ['description', 'recommendation', 'chatbot', 'email_personalization'];
        $aiModels = ['mistral:7b', 'llama3.1:8b'];

        for ($i = 0; $i < 200; ++$i) {
            $log = new AiLog();

            $type = $faker->randomElement($aiTypes);
            $model = $faker->randomElement($aiModels);

            $prompt = match ($type) {
                'description' => 'Rédige une description attractive et réaliste pour ce restaurant.',
                'recommendation' => 'Recommande 3 axes d’amélioration pour augmenter la valeur perçue avant l’enchère.',
                'chatbot' => 'Réponds à une question d’investisseur sur le bail et le CA.',
                'email_personalization' => 'Personnalise un email de relance suite à une visite d’annonce.',
                default => 'Instruction IA générique.',
            };

            $log->setType($type);
            $log->setModel($model);
            $log->setPrompt($prompt.' Contexte: '.$faker->sentence(18));
            $log->setResponse($faker->paragraphs(2, true));
            $log->setToken($faker->optional(0.8)->numberBetween(120, 2200));
            if ($faker->boolean(80)) {
                $duration = $faker->randomFloat(3, 0.12, 8.5);
                $log->setDuration(number_format($duration, 3, '.', ''));
            } else {
                $log->setDuration(null);
            }

            $log->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-6 months', 'now')));

            $log->setRestaurant($faker->optional(0.7)->randomElement($restaurants)); // nullable
            $log->setUser($faker->optional(0.6)->randomElement(array_merge($buyers, $owners, $admins))); // nullable

            $manager->persist($log);
        }

        $manager->flush();
    }

    private function slugify(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = preg_replace('~[^\pL\d]+~u', '-', $s) ?? $s;
        $s = trim($s, '-');
        $s = iconv('utf-8', 'us-ascii//TRANSLIT', $s) ?: $s;
        $s = preg_replace('~[^-\w]+~', '', $s) ?? $s;
        $s = preg_replace('~-+~', '-', $s) ?? $s;

        return $s ?: 'n-a';
    }
}
