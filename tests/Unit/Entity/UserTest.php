<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Restaurant;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setPassword('hashed_password');

        return $user;
    }

    public function testGettersAndSetters(): void
    {
        $user = $this->createUser();

        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame('hashed_password', $user->getPassword());
    }

    public function testUserIdentifier(): void
    {
        $user = $this->createUser();

        $this->assertSame('test@example.com', $user->getUserIdentifier());
    }

    public function testDefaultRolesContainsRoleUser(): void
    {
        $user = new User();

        $roles = $user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
    }

    public function testSetRolesAddsRoleUser(): void
    {
        $user = $this->createUser();
        $user->setRoles(['ROLE_ADMIN']);

        $roles = $user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
    }

    public function testSetRolesNoDuplicates(): void
    {
        $user = $this->createUser();
        $user->setRoles(['ROLE_USER', 'ROLE_VENDOR']);

        $roles = $user->getRoles();

        $this->assertCount(2, $roles);
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_VENDOR', $roles);
    }

    public function testPhoneNumber(): void
    {
        $user = $this->createUser();

        $this->assertNull($user->getPhoneNumber());

        $user->setPhoneNumber('+33612345678');
        $this->assertSame('+33612345678', $user->getPhoneNumber());
    }

    public function testIsVerifiedDefault(): void
    {
        $user = new User();

        $this->assertNull($user->isVerified());
    }

    public function testSetIsVerified(): void
    {
        $user = $this->createUser();
        $user->setIsVerified(true);

        $this->assertTrue($user->isVerified());

        $user->setIsVerified(false);
        $this->assertFalse($user->isVerified());
    }

    public function testIsSuspendedDefault(): void
    {
        $user = new User();

        $this->assertNull($user->isSuspended());
    }

    public function testSetIsSuspended(): void
    {
        $user = $this->createUser();
        $user->setIsSuspended(true);

        $this->assertTrue($user->isSuspended());
    }

    public function testCreatedAtSetOnConstruction(): void
    {
        $before = new \DateTimeImmutable();
        $user = new User();
        $after = new \DateTimeImmutable();

        $createdAt = $user->getCreatedAt();

        $this->assertNotNull($createdAt);
        $this->assertGreaterThanOrEqual($before, $createdAt);
        $this->assertLessThanOrEqual($after, $createdAt);
    }

    public function testUpdatedAtSetOnConstruction(): void
    {
        $user = new User();

        $this->assertNotNull($user->getUpdatedAt());
    }

    public function testRestaurantCollection(): void
    {
        $user = $this->createUser();
        $restaurant = new Restaurant();
        $restaurant->setName('Test Restaurant');
        $restaurant->setAddress('123 Test St');
        $restaurant->setLatitude(0.0);
        $restaurant->setLongitude(0.0);
        $restaurant->setCapacity(50);
        $restaurant->setAskingPrice('100000');
        $restaurant->setCreatedAt(new \DateTimeImmutable());

        $this->assertCount(0, $user->getRestaurants());

        $user->addRestaurant($restaurant);
        $this->assertCount(1, $user->getRestaurants());
        $this->assertSame($user, $restaurant->getOwner());

        $user->removeRestaurant($restaurant);
        $this->assertCount(0, $user->getRestaurants());
    }

    public function testSerializeHashesPassword(): void
    {
        $user = $this->createUser();
        $user->setPassword('secret_password');

        $serialized = $user->__serialize();

        $passwordKey = "\0App\Entity\User\0password";
        $this->assertArrayHasKey($passwordKey, $serialized);
        $this->assertNotSame('secret_password', $serialized[$passwordKey]);
    }
}
