<?php

namespace App\Tests\Functional;

use App\Entity\Order;
use App\Entity\Refund;
use App\Entity\Restaurant;
use App\Entity\User;
use App\Entity\VendorRequest;
use App\Enum\StatusRefundEnum;
use App\Enum\StatusRestaurantEnum;
use App\Enum\StatusVendorRequestEnum;
use App\Repository\RefundRepository;
use App\Repository\RestaurantRepository;
use App\Repository\VendorRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Vérifie les méthodes COUNT utilisées pour les pastilles admin (remboursements, vendeurs, modération).
 */
final class RepositoryPendingCountsTest extends KernelTestCase
{
    public function testRefundRepositoryCountPendingForAdmin(): void
    {
        $this->skipUnlessSchemaReady();

        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $refundRepository = $container->get(RefundRepository::class);
        self::assertInstanceOf(RefundRepository::class, $refundRepository);

        $buyer = $this->newUser($em, 'refund-count-'.bin2hex(random_bytes(4)).'@example.test');

        $order = new Order();
        $order->setReference('ORD-'.bin2hex(random_bytes(4)));
        $order->setTotalAmount('100.00');
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setBuyer($buyer);
        $em->persist($order);

        $pending = new Refund();
        $pending->setAmount('10.00');
        $pending->setReasonRefund('Test pending');
        $pending->setOrder($order);
        $pending->setRequestedBy($buyer);
        $pending->setCreatedAt(new \DateTimeImmutable());
        $pending->setStatus(StatusRefundEnum::EN_ATTENTE);
        $em->persist($pending);

        $processed = new Refund();
        $processed->setAmount('5.00');
        $processed->setReasonRefund('Test processed');
        $processed->setOrder($order);
        $processed->setRequestedBy($buyer);
        $processed->setCreatedAt(new \DateTimeImmutable());
        $processed->setStatus(StatusRefundEnum::TRAITE);
        $em->persist($processed);

        $em->flush();

        try {
            self::assertSame(1, $refundRepository->countPendingForAdmin());
        } finally {
            $em->remove($pending);
            $em->remove($processed);
            $em->remove($order);
            $em->remove($buyer);
            $em->flush();
        }
    }

    public function testVendorRequestRepositoryCountPendingForAdmin(): void
    {
        $this->skipUnlessSchemaReady();

        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $vendorRequestRepository = $container->get(VendorRequestRepository::class);
        self::assertInstanceOf(VendorRequestRepository::class, $vendorRequestRepository);

        $userA = $this->newUser($em, 'vr-pending-'.bin2hex(random_bytes(4)).'@example.test');
        $userB = $this->newUser($em, 'vr-done-'.bin2hex(random_bytes(4)).'@example.test');

        $pending = new VendorRequest();
        $pending->setUser($userA);
        $pending->setMotivation('Je souhaite vendre sur la plateforme pour tester le compteur.');
        $em->persist($pending);

        $approved = new VendorRequest();
        $approved->setUser($userB);
        $approved->setMotivation('Autre motivation pour la même série de tests.');
        $approved->setStatus(StatusVendorRequestEnum::APPROUVE);
        $approved->setProcessedAt(new \DateTimeImmutable());
        $em->persist($approved);

        $em->flush();

        try {
            self::assertSame(1, $vendorRequestRepository->countPendingForAdmin());
        } finally {
            $em->remove($pending);
            $em->remove($approved);
            $em->remove($userA);
            $em->remove($userB);
            $em->flush();
        }
    }

    public function testRestaurantRepositoryCountPendingValidation(): void
    {
        $this->skipUnlessSchemaReady();

        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        $restaurantRepository = $container->get(RestaurantRepository::class);
        self::assertInstanceOf(RestaurantRepository::class, $restaurantRepository);

        $owner = $this->newUser($em, 'resto-moderation-'.bin2hex(random_bytes(4)).'@example.test');

        $before = $restaurantRepository->countPendingValidation();

        $inModeration = new Restaurant();
        $inModeration->setName('Resto modération test');
        $inModeration->setAddress('1 rue du Test');
        $inModeration->setLatitude(48.85);
        $inModeration->setLongitude(2.35);
        $inModeration->setCapacity(40);
        $inModeration->setAskingPrice('150000.00');
        $inModeration->setStatus(StatusRestaurantEnum::EN_MODERATION);
        $inModeration->setOwner($owner);
        $inModeration->setCreatedAt(new \DateTimeImmutable());
        $em->persist($inModeration);

        $published = new Restaurant();
        $published->setName('Resto publié test');
        $published->setAddress('2 rue du Test');
        $published->setLatitude(48.86);
        $published->setLongitude(2.36);
        $published->setCapacity(30);
        $published->setAskingPrice('90000.00');
        $published->setStatus(StatusRestaurantEnum::PUBLIE);
        $published->setOwner($owner);
        $published->setCreatedAt(new \DateTimeImmutable());
        $em->persist($published);

        $em->flush();

        try {
            self::assertSame($before + 1, $restaurantRepository->countPendingValidation());
        } finally {
            $em->remove($inModeration);
            $em->remove($published);
            $em->remove($owner);
            $em->flush();
        }
    }

    private function skipUnlessSchemaReady(): void
    {
        try {
            self::bootKernel();
            $em = static::getContainer()->get('doctrine.orm.entity_manager');
            self::assertInstanceOf(EntityManagerInterface::class, $em);
            $connection = $em->getConnection();
            $connection->executeQuery('SELECT 1');
            $schemaManager = $connection->createSchemaManager();
            foreach (['user', 'vendor_request', 'order', 'refund', 'restaurant'] as $table) {
                if (!$schemaManager->tablesExist([$table])) {
                    self::markTestSkipped(sprintf('Table requise absente : %s', $table));
                }
            }
        } catch (\Throwable $e) {
            self::markTestSkipped('Base de test indisponible : '.$e->getMessage());
        } finally {
            self::ensureKernelShutdown();
        }
    }

    private function newUser(EntityManagerInterface $em, string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('test-password-functional');
        $user->setFirstName('Test');
        $user->setLastName('Counts');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);
        $user->setIsSuspended(false);
        $em->persist($user);
        $em->flush();

        return $user;
    }
}
