<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EncheresRecommendationsAccessTest extends WebTestCase
{
    public function testRecommendationsEndpointIsNotPubliclySuccessfulWithoutSession(): void
    {
        $client = static::createClient();
        $client->request('GET', '/encheres/recommendations');

        self::assertNotSame(200, $client->getResponse()->getStatusCode());
    }
}
