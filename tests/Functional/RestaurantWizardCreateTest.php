<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RestaurantWizardCreateTest extends WebTestCase
{
    // --- Unauthenticated access ---

    public function testNewRedirectsToLoginWhenUnauthenticated(): void
    {
        $client = static::createClient();

        $client->request('GET', '/restaurant/nouveau');

        $this->assertResponseRedirects();
        $location = $client->getResponse()->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringContainsString('/login', $location);
    }

    public function testStep1RedirectsToLoginWhenUnauthenticated(): void
    {
        $client = static::createClient();

        $client->request('GET', '/restaurant/nouveau/1');

        $this->assertResponseRedirects();
        $location = $client->getResponse()->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringContainsString('/login', $location);
    }

    public function testStep2RedirectsToLoginWhenUnauthenticated(): void
    {
        $client = static::createClient();

        $client->request('GET', '/restaurant/nouveau/2');

        $this->assertResponseRedirects();
        $location = $client->getResponse()->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringContainsString('/login', $location);
    }

    public function testStep4RedirectsToLoginWhenUnauthenticated(): void
    {
        $client = static::createClient();

        $client->request('POST', '/restaurant/nouveau/4');

        $this->assertResponseRedirects();
        $location = $client->getResponse()->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringContainsString('/login', $location);
    }

    // --- Invalid step format returns 404 ---

    public function testInvalidStepReturns404(): void
    {
        $client = static::createClient();

        $client->request('GET', '/restaurant/nouveau/5');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testNonNumericStepReturns404(): void
    {
        $client = static::createClient();

        $client->request('GET', '/restaurant/nouveau/abc');

        $this->assertResponseStatusCodeSame(404);
    }
}
