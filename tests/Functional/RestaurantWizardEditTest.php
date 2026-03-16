<?php

namespace App\Tests\Functional;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\StatusRestaurantEnum;
use App\Exception\RestaurantNotFoundException;
use App\Service\RestaurantService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RestaurantWizardEditTest extends WebTestCase
{
    private function createTestUser(int $id = 1): User
    {
        $user = new User();
        $user->setEmail('vendor@test.com');
        $user->setFirstName('Jean');
        $user->setLastName('Vendor');
        $user->setPassword('hashed');
        $user->setRoles(['ROLE_USER', 'ROLE_VENDOR']);

        $reflection = new \ReflectionClass($user);
        $reflection->getProperty('id')->setValue($user, $id);

        return $user;
    }

    private function createTestRestaurant(int $id, User $owner): Restaurant
    {
        $restaurant = new Restaurant();
        $restaurant->setName('Le Test');
        $restaurant->setAddress('12 rue de la Paix, Paris');
        $restaurant->setLatitude(48.8566);
        $restaurant->setLongitude(2.3522);
        $restaurant->setCapacity(50);
        $restaurant->setAskingPrice('300000.00');
        $restaurant->setStatus(StatusRestaurantEnum::PUBLIE);
        $restaurant->setOwner($owner);
        $restaurant->setCreatedAt(new \DateTimeImmutable());

        $reflection = new \ReflectionClass($restaurant);
        $reflection->getProperty('id')->setValue($restaurant, $id);

        return $restaurant;
    }

    // --- Unauthenticated access ---

    public function testEditRedirectsToLoginWhenUnauthenticated(): void
    {
        $client = static::createClient();

        $owner = $this->createTestUser(1);
        $restaurant = $this->createTestRestaurant(10, $owner);

        $mockService = $this->createMock(RestaurantService::class);
        $mockService->method('findOrFail')->willReturn($restaurant);

        static::getContainer()->set(RestaurantService::class, $mockService);

        $client->request('GET', '/restaurant/10/modifier');

        $this->assertResponseRedirects();
    }

    public function testEditStepRedirectsToLoginWhenUnauthenticated(): void
    {
        $client = static::createClient();

        $owner = $this->createTestUser(1);
        $restaurant = $this->createTestRestaurant(10, $owner);

        $mockService = $this->createMock(RestaurantService::class);
        $mockService->method('findOrFail')->willReturn($restaurant);

        static::getContainer()->set(RestaurantService::class, $mockService);

        $client->request('GET', '/restaurant/10/modifier/1');

        $this->assertResponseRedirects();
    }

    // --- 404 when restaurant not found ---

    public function testEditReturns404WhenRestaurantNotFound(): void
    {
        $client = static::createClient();

        $mockService = $this->createMock(RestaurantService::class);
        $mockService
            ->expects($this->once())
            ->method('findOrFail')
            ->with(999)
            ->willThrowException(new RestaurantNotFoundException(999));

        static::getContainer()->set(RestaurantService::class, $mockService);

        $client->request('GET', '/restaurant/999/modifier');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testEditStepReturns404WhenRestaurantNotFound(): void
    {
        $client = static::createClient();

        $mockService = $this->createMock(RestaurantService::class);
        $mockService
            ->method('findOrFail')
            ->willThrowException(new RestaurantNotFoundException(999));

        static::getContainer()->set(RestaurantService::class, $mockService);

        $client->request('GET', '/restaurant/999/modifier/1');

        $this->assertResponseStatusCodeSame(404);
    }

    // --- Invalid formats ---

    public function testEditWithInvalidIdReturns404(): void
    {
        $client = static::createClient();

        $client->request('GET', '/restaurant/invalid/modifier');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testEditStepWithInvalidStepReturns404(): void
    {
        $client = static::createClient();

        $client->request('GET', '/restaurant/10/modifier/9');

        $this->assertResponseStatusCodeSame(404);
    }
}
