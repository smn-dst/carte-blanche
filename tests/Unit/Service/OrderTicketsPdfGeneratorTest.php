<?php

namespace App\Tests\Unit\Service;

use App\Entity\Order;
use App\Service\OrderTicketsPdfGenerator;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class OrderTicketsPdfGeneratorTest extends TestCase
{
    public function testGenerateReturnsPdfBinaryForEmptyTickets(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturn(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><p>Test</p></body></html>'
        );

        $projectDir = sys_get_temp_dir().'/cb-pdf-test-'.bin2hex(random_bytes(4));
        self::assertTrue(mkdir($projectDir.'/public/uploads/qrcodes', 0777, true));

        try {
            $generator = new OrderTicketsPdfGenerator($twig, $projectDir);

            $order = new Order();
            $order->setReference('CB-TEST-REF');
            $order->setTotalAmount('10.00');
            $order->setCreatedAt(new \DateTimeImmutable('2025-01-15 10:00:00'));

            $pdf = $generator->generate($order);

            self::assertNotSame('', $pdf);
            self::assertSame('%PDF', substr($pdf, 0, 4));
        } finally {
            if (is_dir($projectDir)) {
                $this->deleteTree($projectDir);
            }
        }
    }

    private function deleteTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }
            $path = $dir.'/'.$item;
            if (is_dir($path)) {
                $this->deleteTree($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
