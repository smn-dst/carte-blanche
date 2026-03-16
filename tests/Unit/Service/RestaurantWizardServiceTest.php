<?php

namespace App\Tests\Unit\Service;

use App\Dto\RestaurantStep1Dto;
use App\Dto\RestaurantStep2Dto;
use App\Dto\RestaurantStep3Dto;
use App\Dto\RestaurantStep4Dto;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Service\RestaurantWizardService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class RestaurantWizardServiceTest extends TestCase
{
    private SessionInterface&MockObject $session;
    private RequestStack&MockObject $requestStack;
    private CategoryRepository&MockObject $categoryRepository;
    private RestaurantWizardService $service;

    /** @var array<string, mixed> */
    private array $sessionStore = [];

    protected function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);

        $this->requestStack
            ->method('getSession')
            ->willReturn($this->session);

        // Simulate session get/set/remove with in-memory array
        $this->session
            ->method('get')
            ->willReturnCallback(function (string $key, mixed $default = null): mixed {
                return $this->sessionStore[$key] ?? $default;
            });

        $this->session
            ->method('set')
            ->willReturnCallback(function (string $key, mixed $value): void {
                $this->sessionStore[$key] = $value;
            });

        $this->session
            ->method('remove')
            ->willReturnCallback(function (string $key): void {
                unset($this->sessionStore[$key]);
            });

        $this->service = new RestaurantWizardService($this->requestStack, $this->categoryRepository);
    }

    // --- saveStep / getStepDto cycle ---

    public function testSaveAndGetStep1Dto(): void
    {
        $category = new Category();
        $reflection = new \ReflectionClass($category);
        $idProp = $reflection->getProperty('id');
        $idProp->setValue($category, 5);

        $dto = new RestaurantStep1Dto(
            name: 'Mon Restaurant',
            description: 'Super description',
            categories: [$category],
        );

        $this->categoryRepository
            ->expects($this->once())
            ->method('find')
            ->with(5)
            ->willReturn($category);

        $this->service->saveStep(1, $dto);
        $result = $this->service->getStep1Dto();

        $this->assertSame('Mon Restaurant', $result->name);
        $this->assertSame('Super description', $result->description);
        $this->assertCount(1, $result->categories);
        $this->assertSame($category, $result->categories[0]);
    }

    public function testSaveAndGetStep2Dto(): void
    {
        $dto = new RestaurantStep2Dto(
            address: '12 rue de Paris',
            latitude: 48.8566,
            longitude: 2.3522,
            capacity: 60,
        );

        $this->service->saveStep(2, $dto);
        $result = $this->service->getStep2Dto();

        $this->assertSame('12 rue de Paris', $result->address);
        $this->assertSame(48.8566, $result->latitude);
        $this->assertSame(2.3522, $result->longitude);
        $this->assertSame(60, $result->capacity);
    }

    public function testSaveAndGetStep3Dto(): void
    {
        $dto = new RestaurantStep3Dto(
            askingPrice: '250000.00',
            annualRevenue: '500000.00',
            rent: '3000.00',
            leaseRemaining: 36,
            pappersUrl: null,
        );

        $this->service->saveStep(3, $dto);
        $result = $this->service->getStep3Dto();

        $this->assertSame('250000.00', $result->askingPrice);
        $this->assertSame('500000.00', $result->annualRevenue);
        $this->assertSame('3000.00', $result->rent);
        $this->assertSame(36, $result->leaseRemaining);
        $this->assertNull($result->pappersUrl);
    }

    public function testGetStep4DtoAlwaysReturnsEmptyUploadedImages(): void
    {
        $result = $this->service->getStep4Dto();

        $this->assertInstanceOf(RestaurantStep4Dto::class, $result);
        $this->assertEmpty($result->uploadedImages);
    }

    public function testGetStep1DtoReturnsEmptyWhenNoSession(): void
    {
        $result = $this->service->getStep1Dto();

        $this->assertSame('', $result->name);
        $this->assertNull($result->description);
        $this->assertEmpty($result->categories);
    }

    // --- buildFullDto ---

    public function testBuildFullDtoAssemblesAllSteps(): void
    {
        // Seed session with steps 1-3
        $this->sessionStore[RestaurantWizardService::SESSION_KEY] = [
            'step1' => ['name' => 'Test', 'description' => null, 'categoryIds' => []],
            'step2' => ['address' => '5 rue Test', 'latitude' => 45.0, 'longitude' => 3.0, 'capacity' => 80],
            'step3' => ['askingPrice' => '200000.00', 'annualRevenue' => null, 'rent' => null, 'leaseRemaining' => null, 'pappersUrl' => null],
        ];

        $step4 = new RestaurantStep4Dto(maxCapacity: 150);

        $full = $this->service->buildFullDto($step4);

        $this->assertSame('Test', $full->name);
        $this->assertSame('5 rue Test', $full->address);
        $this->assertSame(45.0, $full->latitude);
        $this->assertSame(80, $full->capacity);
        $this->assertSame('200000.00', $full->askingPrice);
        $this->assertSame(150, $full->maxCapacity);
    }

    // --- isStepAccessible ---

    public function testStep1IsAlwaysAccessibleAndClearsSession(): void
    {
        $this->sessionStore[RestaurantWizardService::SESSION_KEY] = [
            'step1' => ['name' => 'Old', 'description' => null, 'categoryIds' => []],
        ];

        $result = $this->service->isStepAccessible(1);

        $this->assertTrue($result);
        $this->assertArrayNotHasKey(RestaurantWizardService::SESSION_KEY, $this->sessionStore);
    }

    public function testStep2NotAccessibleWhenStep1Missing(): void
    {
        $result = $this->service->isStepAccessible(2);

        $this->assertFalse($result);
    }

    public function testStep2AccessibleWhenStep1Present(): void
    {
        $this->sessionStore[RestaurantWizardService::SESSION_KEY] = [
            'step1' => ['name' => 'Test', 'description' => null, 'categoryIds' => []],
        ];

        $result = $this->service->isStepAccessible(2);

        $this->assertTrue($result);
    }

    public function testStep4NotAccessibleWhenStep3Missing(): void
    {
        $this->sessionStore[RestaurantWizardService::SESSION_KEY] = [
            'step1' => ['name' => 'Test', 'description' => null, 'categoryIds' => []],
            'step2' => ['address' => '...', 'latitude' => 1.0, 'longitude' => 1.0, 'capacity' => 10],
        ];

        $result = $this->service->isStepAccessible(4);

        $this->assertFalse($result);
    }

    // --- clearWizardSession ---

    public function testClearWizardSessionRemovesData(): void
    {
        $this->sessionStore[RestaurantWizardService::SESSION_KEY] = ['step1' => []];

        $this->service->clearWizardSession();

        $this->assertArrayNotHasKey(RestaurantWizardService::SESSION_KEY, $this->sessionStore);
    }
}
