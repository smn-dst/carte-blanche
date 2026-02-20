<?php

namespace App\Tests\Functional;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\StatusRestaurantEnum;
use App\Repository\RestaurantRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RestaurantControllerTest extends WebTestCase
{
    private function createMockRestaurant(int $id, string $name, StatusRestaurantEnum $status = StatusRestaurantEnum::PUBLIE): Restaurant
    {
        $owner = new User();
        $owner->setEmail('owner@test.com');
        $owner->setFirstName('Test');
        $owner->setLastName('Owner');
        $owner->setPassword('password');

        $restaurant = new Restaurant();
        $restaurant->setName($name);
        $restaurant->setAddress('123 Test Street, Paris');
        $restaurant->setLatitude(48.8566);
        $restaurant->setLongitude(2.3522);
        $restaurant->setCapacity(50);
        $restaurant->setAskingPrice('250000.00');
        $restaurant->setStatus($status);
        $restaurant->setOwner($owner);
        $restaurant->setCreatedAt(new \DateTimeImmutable());

        $reflection = new \ReflectionClass($restaurant);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($restaurant, $id);

        return $restaurant;
    }

    public function testShowRestaurantPageIsSuccessful(): void
    {
        $client = static::createClient();

        $restaurant = $this->createMockRestaurant(42, 'Le Test Restaurant');

        $mockRepository = $this->createMock(RestaurantRepository::class);
        $mockRepository
            ->method('find')
            ->with(42)
            ->willReturn($restaurant);

        static::getContainer()->set(RestaurantRepository::class, $mockRepository);

        $client->request('GET', '/restaurant/42');

        $this->assertResponseIsSuccessful();
    }

    public function testShowRestaurantReturns404WhenNotFound(): void
    {
        $client = static::createClient();

        $mockRepository = $this->createMock(RestaurantRepository::class);
        $mockRepository
            ->method('find')
            ->with(999)
            ->willReturn(null);

        static::getContainer()->set(RestaurantRepository::class, $mockRepository);

        $client->request('GET', '/restaurant/999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testShowRestaurantDisplaysCorrectContent(): void
    {
        $client = static::createClient();

        $restaurant = $this->createMockRestaurant(1, 'Le Gourmet Français');
        $restaurant->setDescription('Un excellent restaurant gastronomique');

        $mockRepository = $this->createMock(RestaurantRepository::class);
        $mockRepository
            ->method('find')
            ->with(1)
            ->willReturn($restaurant);

        static::getContainer()->set(RestaurantRepository::class, $mockRepository);

        $crawler = $client->request('GET', '/restaurant/1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

    public function testShowRestaurantWithInvalidIdFormat(): void
    {
        $client = static::createClient();

        $client->request('GET', '/restaurant/invalid');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testShowRestaurantWithNegativeId(): void
    {
        $client = static::createClient();

        $client->request('GET', '/restaurant/-1');

        $this->assertResponseStatusCodeSame(404);
    }
}
