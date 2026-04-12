<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TicketControllerTest extends WebTestCase
{
    public function testMyTicketsRedirectsAnonymousUserToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mes-reservations');

        self::assertResponseRedirects();
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testMyTicketsPageIsSuccessfulWhenAuthenticated(): void
    {
        $client = static::createClient();
        $this->ensureDatabaseIsReady();

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $user = $this->createPersistedUser($entityManager, 'buyer-tickets-'.bin2hex(random_bytes(4)).'@example.test');

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->method('findBy')->willReturn([]);
        static::getContainer()->set(OrderRepository::class, $orderRepository);

        $client->loginUser($user);
        $client->request('GET', '/mes-reservations');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Mes réservations');
        self::assertSelectorTextContains('body', 'Aucune réservation');

        $entityManager->remove($user);
        $entityManager->flush();
    }

    private function ensureDatabaseIsReady(): void
    {
        try {
            $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
            self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
            $connection = $entityManager->getConnection();
            $connection->executeQuery('SELECT 1');
            $schemaManager = $connection->createSchemaManager();
            if (!$schemaManager->tablesExist(['user'])) {
                $this->markTestSkipped('Table "user" absente en base de test.');
            }
        } catch (\Throwable $exception) {
            $this->markTestSkipped('Base de test indisponible : '.$exception->getMessage());
        }
    }

    private function createPersistedUser(EntityManagerInterface $entityManager, string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('test-password-functional');
        $user->setFirstName('Test');
        $user->setLastName('Buyer');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);
        $user->setIsSuspended(false);

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
