<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Restaurant;
use App\Entity\RestaurantEmbedding;
use App\Entity\User;
use App\Entity\UserPreferenceEmbedding;
use App\Enum\StatusRestaurantEnum;
use App\Repository\RestaurantEmbeddingRepository;
use App\Service\RecommendationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class RecommendationServiceTest extends TestCase
{
    private RestaurantEmbeddingRepository&MockObject $embeddingRepository;

    private LoggerInterface&MockObject $logger;

    private RecommendationService $service;

    protected function setUp(): void
    {
        $this->embeddingRepository = $this->createMock(RestaurantEmbeddingRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new RecommendationService($this->embeddingRepository, $this->logger);
    }

    public function testReturnsEmptyWhenUserHasNoEmbedding(): void
    {
        $user = new User();
        $user->setEmail('u@test.fr');
        $user->setFirstName('U');
        $user->setLastName('Ser');
        $user->setPassword('x');

        self::assertSame([], $this->service->getTopRecommendations($user));
    }

    public function testReturnsEmptyWhenNoRestaurantEmbeddings(): void
    {
        $user = $this->createUserWithEmbedding([1.0, 0.0]);

        $this->embeddingRepository->method('findAllWithNonEmptyEmbedding')->willReturn([]);

        self::assertSame([], $this->service->getTopRecommendations($user));
    }

    public function testReturnsRankedRecommendationsWithStaticExplanation(): void
    {
        $user = $this->createUserWithEmbedding([1.0, 0.0, 0.0]);

        $owner = new User();
        $owner->setEmail('o@test.fr');
        $owner->setFirstName('O');
        $owner->setLastName('W');
        $owner->setPassword('x');

        $restaurant = new Restaurant();
        $restaurant->setName('Match');
        $restaurant->setAddress('12 rue Victor Hugo, Toulouse');
        $restaurant->setLatitude(43.6);
        $restaurant->setLongitude(1.44);
        $restaurant->setCapacity(80);
        $restaurant->setAskingPrice('400000.00');
        $restaurant->setAnnualRevenue('900000.00');
        $restaurant->setStatus(StatusRestaurantEnum::PUBLIE);
        $restaurant->setOwner($owner);
        $restaurant->setCreatedAt(new \DateTimeImmutable());

        $reflection = new \ReflectionClass($restaurant);
        $reflection->getProperty('id')->setValue($restaurant, 7);

        $entityEmb = new RestaurantEmbedding();
        $entityEmb->setContent('desc');
        $entityEmb->setEmbedding([1.0, 0.0, 0.0]);
        $entityEmb->setUpdatedAt(new \DateTimeImmutable());
        $entityEmb->setRestaurant($restaurant);

        $this->embeddingRepository->method('findAllWithNonEmptyEmbedding')->willReturn([$entityEmb]);

        $results = $this->service->getTopRecommendations($user, 3);

        self::assertCount(1, $results);
        self::assertSame($restaurant, $results[0]['restaurant']);
        self::assertSame(1.0, $results[0]['score']);
        self::assertStringContainsString('compatibilité', $results[0]['explanation']);
        self::assertStringContainsString('CA annuel', $results[0]['explanation']);
    }

    /** @param array<int, float> $vector */
    private function createUserWithEmbedding(array $vector): User
    {
        $user = new User();
        $user->setEmail('u@test.fr');
        $user->setFirstName('U');
        $user->setLastName('Ser');
        $user->setPassword('x');

        $upe = new UserPreferenceEmbedding();
        $upe->setPreferencesText('Préférences');
        $upe->setEmbedding($vector);
        $upe->setPreferencesData([
            'preferredCity' => 'Toulouse',
        ]);
        $upe->setUpdatedAt(new \DateTimeImmutable());
        $upe->setUser($user);

        $user->setUserPreferenceEmbedding($upe);

        return $user;
    }
}
