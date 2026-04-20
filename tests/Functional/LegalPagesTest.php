<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LegalPagesTest extends WebTestCase
{
    /** @return iterable<string, array{0: string}> */
    public static function legalPathsProvider(): iterable
    {
        yield 'rgpd' => ['/politique-de-confidentialite'];
        yield 'cgv' => ['/conditions-generales-de-vente'];
        yield 'cgu' => ['/conditions-generales-utilisation'];
    }

    #[DataProvider('legalPathsProvider')]
    public function testLegalPageIsSuccessful(string $path): void
    {
        $client = static::createClient();
        $client->request('GET', $path);

        self::assertResponseIsSuccessful();
    }
}
