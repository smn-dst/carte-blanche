<?php

namespace App\Tests\Functional;

use App\Dto\RestaurantInputDto;
use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\StatusRestaurantEnum;
use App\Exception\RestaurantNotFoundException;
use App\Service\RestaurantService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RestaurantCrudControllerTest extends WebTestCase
{
    private function createTestUser(int $id = 1, array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPassword('hashed_password');
        $user->setRoles($roles);

        $reflection = new \ReflectionClass($user);
        $idProp = $reflection->getProperty('id');
        $idProp->setValue($user, $id);

        return $user;
    }

    private function createTestRestaurant(int $id, User $owner, StatusRestaurantEnum $status = StatusRestaurantEnum::PUBLIE): Restaurant
    {
        $restaurant = new Restaurant();
        $restaurant->setName('Le Test Restaurant');
        $restaurant->setAddress('12 rue de la Paix, Paris');
        $restaurant->setLatitude(48.8566);
        $restaurant->setLongitude(2.3522);
        $restaurant->setCapacity(50);
        $restaurant->setAskingPrice('300000.00');
        $restaurant->setStatus($status);
        $restaurant->setOwner($owner);
        $restaurant->setCreatedAt(new \DateTimeImmutable());

        $reflection = new \ReflectionClass($restaurant);
        $idProp = $reflection->getProperty('id');
        $idProp->setValue($restaurant, $id);

        return $restaurant;
    }

    // --- GET /mes-restaurants ---

    public function testIndexRedirectsToLoginWhenUnauthenticated(): void
    {
        $client = static::createClient();

        $client->request('GET', '/mes-restaurants');

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }

    // --- GET /restaurant/nouveau ---

    public function testNewFormRedirectsToLoginWhenUnauthenticated(): void
    {
        $client = static::createClient();

        $client->request('GET', '/restaurant/nouveau');

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }

    // --- GET /restaurant/{id}/modifier ---

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

    public function testEditWithInvalidIdFormatReturns404(): void
    {
        $client = static::createClient();

        $client->request('GET', '/restaurant/invalid/modifier');

        $this->assertResponseStatusCodeSame(404);
    }

    // --- POST /restaurant/{id}/supprimer ---

    public function testDeleteReturns404WhenRestaurantNotFound(): void
    {
        $client = static::createClient();

        $mockService = $this->createMock(RestaurantService::class);
        $mockService
            ->expects($this->once())
            ->method('findOrFail')
            ->with(999)
            ->willThrowException(new RestaurantNotFoundException(999));

        static::getContainer()->set(RestaurantService::class, $mockService);

        $client->request('POST', '/restaurant/999/supprimer', ['_token' => 'invalid']);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteWithInvalidIdFormatReturns404(): void
    {
        $client = static::createClient();

        $client->request('POST', '/restaurant/abc/supprimer');

        $this->assertResponseStatusCodeSame(404);
    }

    // --- GET /restaurant/{id}/modifier (redirect to login when found but not auth) ---

    public function testEditRedirectsToLoginWhenFoundButUnauthenticated(): void
    {
        $client = static::createClient();

        $owner = $this->createTestUser(1);
        $restaurant = $this->createTestRestaurant(10, $owner);

        $mockService = $this->createMock(RestaurantService::class);
        $mockService
            ->method('findOrFail')
            ->with(10)
            ->willReturn($restaurant);

        static::getContainer()->set(RestaurantService::class, $mockService);

        $client->request('GET', '/restaurant/10/modifier');

        $this->assertResponseRedirects();
    }

    // --- GET /mes-restaurants format validation ---

    public function testIndexWithGetMethod(): void
    {
        $client = static::createClient();

        $client->request('GET', '/mes-restaurants');

        // Should redirect to login when not authenticated
        $this->assertTrue(
            $client->getResponse()->isRedirection(),
            'Expected a redirect response for unauthenticated access.'
        );
    }
}
