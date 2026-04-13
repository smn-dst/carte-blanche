<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class VendorRequestWizardTest extends WebTestCase
{
    public function testWizardRedirectsAnonymousUserToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/devenir-vendeur');

        self::assertResponseRedirects();
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testWizardPageIsSuccessfulForAuthenticatedUser(): void
    {
        $client = static::createClient();
        $this->ensureDatabaseIsReady();

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $email = 'vendor-wizard-'.bin2hex(random_bytes(4)).'@example.test';
        $user = $this->createPersistedUser($entityManager, $email, ['ROLE_USER']);

        try {
            $client->loginUser($user);
            $client->request('GET', '/devenir-vendeur');

            self::assertResponseIsSuccessful();
            self::assertSelectorTextContains('body', 'Devenir vendeur');
            self::assertSelectorExists('form#vendor-wizard-form');
            self::assertSelectorExists('textarea#motivation');
        } finally {
            $entityManager->remove($user);
            $entityManager->flush();
        }
    }

    public function testWizardRedirectsVendorToHome(): void
    {
        $client = static::createClient();
        $this->ensureDatabaseIsReady();

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $email = 'already-vendor-'.bin2hex(random_bytes(4)).'@example.test';
        $user = $this->createPersistedUser($entityManager, $email, ['ROLE_VENDOR']);

        try {
            $client->loginUser($user);
            $client->request('GET', '/devenir-vendeur');

            self::assertResponseRedirects();
            self::assertSame('/', $client->getResponse()->headers->get('Location'));
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

    /**
     * @param list<string> $roles
     */
    private function createPersistedUser(EntityManagerInterface $entityManager, string $email, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('test-password-functional');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles($roles);
        $user->setIsVerified(true);
        $user->setIsSuspended(false);

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
