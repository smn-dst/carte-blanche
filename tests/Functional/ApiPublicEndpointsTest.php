<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\StatusRestaurantEnum;
use App\Repository\CategoryRepository;
use App\Repository\RestaurantRepository;
use App\Service\ChatbotService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ApiPublicEndpointsTest extends WebTestCase
{
    /** @return array<string, mixed> */
    private function decodeJson(KernelBrowser $client): array
    {
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        self::assertJson($content);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    public function testRestaurantsReturnsJsonWithPagination(): void
    {
        $client = static::createClient();

        $restaurantRepo = $this->createMock(RestaurantRepository::class);
        $restaurantRepo->method('searchByNameAndCategory')->willReturn([]);
        $restaurantRepo->method('countSearchByNameAndCategory')->willReturn(0);

        static::getContainer()->set(RestaurantRepository::class, $restaurantRepo);

        $client->request('GET', '/api/restaurants');

        self::assertResponseIsSuccessful();

        $data = $this->decodeJson($client);

        self::assertArrayHasKey('data', $data);
        self::assertArrayHasKey('pagination', $data);
        self::assertSame([], $data['data']);
    }

    public function testCategoriesReturnsJson(): void
    {
        $client = static::createClient();

        $categoryRepo = $this->createMock(CategoryRepository::class);
        $categoryRepo->method('findAll')->willReturn([]);

        static::getContainer()->set(CategoryRepository::class, $categoryRepo);

        $client->request('GET', '/api/categories');

        self::assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        self::assertSame('[]', $content);
    }

    public function testRestaurantsMapReturnsGeoJson(): void
    {
        $client = static::createClient();

        $restaurantRepo = $this->createMock(RestaurantRepository::class);
        $restaurantRepo->method('searchByNameAndCategory')->willReturn([]);

        static::getContainer()->set(RestaurantRepository::class, $restaurantRepo);

        $client->request('GET', '/api/restaurants/map');

        self::assertResponseIsSuccessful();

        $data = $this->decodeJson($client);

        self::assertSame('FeatureCollection', $data['type']);
        self::assertSame([], $data['features']);
    }

    public function testRestaurantNotFoundReturns404(): void
    {
        $client = static::createClient();

        $restaurantRepo = $this->createMock(RestaurantRepository::class);
        $restaurantRepo->method('find')->willReturn(null);

        static::getContainer()->set(RestaurantRepository::class, $restaurantRepo);

        $client->request('GET', '/api/restaurants/999');

        self::assertResponseStatusCodeSame(404);

        $data = $this->decodeJson($client);

        self::assertArrayHasKey('error', $data);
    }

    public function testRestaurantDetailReturnsJson(): void
    {
        $client = static::createClient();

        $owner = new User();
        $owner->setEmail('owner@test.fr');
        $owner->setFirstName('O');
        $owner->setLastName('W');
        $owner->setPassword('hash');

        $restaurant = new Restaurant();
        $restaurant->setName('Resto API');
        $restaurant->setAddress('1 rue Test, Paris');
        $restaurant->setLatitude(48.85);
        $restaurant->setLongitude(2.35);
        $restaurant->setCapacity(40);
        $restaurant->setAskingPrice('250000.00');
        $restaurant->setStatus(StatusRestaurantEnum::PUBLIE);
        $restaurant->setOwner($owner);
        $restaurant->setCreatedAt(new \DateTimeImmutable());

        $reflection = new \ReflectionClass($restaurant);
        $reflection->getProperty('id')->setValue($restaurant, 42);

        $restaurantRepo = $this->createMock(RestaurantRepository::class);
        $restaurantRepo->method('find')->willReturnCallback(static function (int $id) use ($restaurant): ?Restaurant {
            return 42 === $id ? $restaurant : null;
        });

        static::getContainer()->set(RestaurantRepository::class, $restaurantRepo);

        $client->request('GET', '/api/restaurants/42');

        self::assertResponseIsSuccessful();

        $data = $this->decodeJson($client);

        self::assertSame(42, $data['id']);
        self::assertSame('Resto API', $data['name']);
    }

    public function testChatbotReturnsAssistantResponse(): void
    {
        $client = static::createClient();

        $chatbot = $this->createMock(ChatbotService::class);
        $chatbot->method('ask')->willReturn('Réponse de test');

        static::getContainer()->set(ChatbotService::class, $chatbot);

        $client->request(
            'POST',
            '/api/chatbot',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['question' => 'Bonjour'], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();

        $data = $this->decodeJson($client);

        self::assertSame('Réponse de test', $data['response']);
    }

    public function testEncheresListReturnsJson(): void
    {
        $client = static::createClient();

        $restaurantRepo = $this->createMock(RestaurantRepository::class);
        $restaurantRepo->method('countSearchByNameAndCategory')->willReturn(0);
        $restaurantRepo->method('searchByNameAndCategory')->willReturn([]);

        static::getContainer()->set(RestaurantRepository::class, $restaurantRepo);

        $client->request('GET', '/api/encheres');

        self::assertResponseIsSuccessful();

        $data = $this->decodeJson($client);

        self::assertArrayHasKey('data', $data);
        self::assertArrayHasKey('pagination', $data);
    }

    public function testChatbotRejectsEmptyQuestion(): void
    {
        $client = static::createClient();

        $chatbot = $this->createMock(ChatbotService::class);
        $chatbot->expects(self::never())->method('ask');

        static::getContainer()->set(ChatbotService::class, $chatbot);

        $client->request(
            'POST',
            '/api/chatbot',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['question' => '   '], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(400);
    }
}
