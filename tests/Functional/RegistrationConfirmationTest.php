<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationConfirmationTest extends WebTestCase
{
    public function testConfirmationRedirectsAnonymousUserToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register/confirmation');

        self::assertResponseRedirects();
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testConfirmationRedirectsToRegisterWhenEmailNotInSession(): void
    {
        $client = static::createClient();
        $this->ensureDatabaseIsReady();

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $user = $this->createPersistedUser($entityManager, 'registered-'.bin2hex(random_bytes(4)).'@example.test');

        try {
            $client->loginUser($user);
            $client->request('GET', '/register/confirmation');

            self::assertResponseRedirects('/register');
        } finally {
            $entityManager->remove($user);
            $entityManager->flush();
        }
    }

    public function testConfirmationDisplaysEmailWhenSessionIsSet(): void
    {
        $client = static::createClient();
        $this->ensureDatabaseIsReady();

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $email = 'pending-confirm-'.bin2hex(random_bytes(4)).'@example.test';
        $user = $this->createPersistedUser($entityManager, $email);

        try {
            $client->loginUser($user);

            $client->request('GET', '/');
            $session = $client->getRequest()->getSession();
            $session->set('registration_pending_email', $email);
            $session->save();

            $client->request('GET', '/register/confirmation');

            self::assertResponseIsSuccessful();
            self::assertSelectorTextContains('body', $email);
            self::assertSelectorTextContains('body', 'Inscription réussie');
        } finally {
            $entityManager->remove($user);
            $entityManager->flush();
        }
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
        $user->setLastName('User');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);
        $user->setIsSuspended(false);

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
