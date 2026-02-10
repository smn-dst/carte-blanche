<?php

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test de base pour valider que PHPUnit fonctionne.
 */
class ExampleTest extends TestCase
{
    public function testAddition(): void
    {
        $this->assertSame(4, 2 + 2);
    }
}
