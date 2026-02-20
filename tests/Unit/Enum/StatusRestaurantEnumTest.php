<?php

namespace App\Tests\Unit\Enum;

use App\Enum\StatusRestaurantEnum;
use PHPUnit\Framework\TestCase;

use function App\Enum\getStatusRestaurantEnum;

class StatusRestaurantEnumTest extends TestCase
{
    public function testAllStatusesHaveStringValues(): void
    {
        $expectedStatuses = [
            'brouillon' => StatusRestaurantEnum::BROUILLON,
            'en_moderation' => StatusRestaurantEnum::EN_MODERATION,
            'publie' => StatusRestaurantEnum::PUBLIE,
            'en_pause' => StatusRestaurantEnum::EN_PAUSE,
            'programme' => StatusRestaurantEnum::PROGRAMME,
            'en_cours' => StatusRestaurantEnum::EN_COURS,
            'terminee' => StatusRestaurantEnum::TERMINEE,
            'vendu' => StatusRestaurantEnum::VENDU,
            'annule' => StatusRestaurantEnum::ANNULE,
        ];

        foreach ($expectedStatuses as $value => $enum) {
            $this->assertSame($value, $enum->value);
        }
    }

    public function testGetStatusRestaurantEnumReturnsCorrectEnum(): void
    {
        $this->assertSame(StatusRestaurantEnum::BROUILLON, getStatusRestaurantEnum('brouillon'));
        $this->assertSame(StatusRestaurantEnum::PUBLIE, getStatusRestaurantEnum('publie'));
        $this->assertSame(StatusRestaurantEnum::PROGRAMME, getStatusRestaurantEnum('programme'));
        $this->assertSame(StatusRestaurantEnum::EN_COURS, getStatusRestaurantEnum('en_cours'));
        $this->assertSame(StatusRestaurantEnum::VENDU, getStatusRestaurantEnum('vendu'));
    }

    public function testGetStatusRestaurantEnumThrowsOnInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid restaurant status');

        getStatusRestaurantEnum('invalid_status');
    }

    public function testEnumCanBeUsedInComparison(): void
    {
        $status = StatusRestaurantEnum::PUBLIE;

        $this->assertTrue(StatusRestaurantEnum::PUBLIE === $status);
        $this->assertFalse(StatusRestaurantEnum::BROUILLON === $status);
    }

    public function testEnumCasesCount(): void
    {
        $cases = StatusRestaurantEnum::cases();

        $this->assertCount(9, $cases);
    }

    /**
     * @dataProvider publishableStatusesProvider
     */
    public function testPublishableStatuses(StatusRestaurantEnum $status, bool $isPublishable): void
    {
        $publishableStatuses = [
            StatusRestaurantEnum::PUBLIE,
            StatusRestaurantEnum::PROGRAMME,
        ];

        $this->assertSame($isPublishable, \in_array($status, $publishableStatuses, true));
    }

    /**
     * @return iterable<string, array{StatusRestaurantEnum, bool}>
     */
    public static function publishableStatusesProvider(): iterable
    {
        yield 'brouillon is not publishable' => [StatusRestaurantEnum::BROUILLON, false];
        yield 'publie is publishable' => [StatusRestaurantEnum::PUBLIE, true];
        yield 'programme is publishable' => [StatusRestaurantEnum::PROGRAMME, true];
        yield 'en_cours is not publishable' => [StatusRestaurantEnum::EN_COURS, false];
        yield 'vendu is not publishable' => [StatusRestaurantEnum::VENDU, false];
        yield 'annule is not publishable' => [StatusRestaurantEnum::ANNULE, false];
    }
}
