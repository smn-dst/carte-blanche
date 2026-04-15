<?php

namespace App\Tests\Unit\Twig;

use App\Repository\RefundRepository;
use App\Repository\RestaurantRepository;
use App\Repository\VendorRequestRepository;
use App\Twig\AdminPendingExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\TwigFunction;

final class AdminPendingExtensionTest extends TestCase
{
    public function testReturnsZerosWhenNotAdmin(): void
    {
        $security = $this->createMock(Security::class);
        $security->expects($this->once())->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);

        $refundRepository = $this->createMock(RefundRepository::class);
        $refundRepository->expects($this->never())->method('countPendingForAdmin');

        $vendorRequestRepository = $this->createMock(VendorRequestRepository::class);
        $vendorRequestRepository->expects($this->never())->method('countPendingForAdmin');

        $restaurantRepository = $this->createMock(RestaurantRepository::class);
        $restaurantRepository->expects($this->never())->method('countPendingValidation');

        $extension = new AdminPendingExtension(
            $security,
            $refundRepository,
            $vendorRequestRepository,
            $restaurantRepository,
        );

        self::assertSame(
            [
                'refunds' => 0,
                'vendor_requests' => 0,
                'restaurant_moderation' => 0,
            ],
            $extension->getAdminPendingCounts(),
        );
    }

    public function testReturnsRepositoryCountsWhenAdmin(): void
    {
        $security = $this->createMock(Security::class);
        $security->expects($this->once())->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);

        $refundRepository = $this->createMock(RefundRepository::class);
        $refundRepository->expects($this->once())->method('countPendingForAdmin')->willReturn(2);

        $vendorRequestRepository = $this->createMock(VendorRequestRepository::class);
        $vendorRequestRepository->expects($this->once())->method('countPendingForAdmin')->willReturn(5);

        $restaurantRepository = $this->createMock(RestaurantRepository::class);
        $restaurantRepository->expects($this->once())->method('countPendingValidation')->willReturn(1);

        $extension = new AdminPendingExtension(
            $security,
            $refundRepository,
            $vendorRequestRepository,
            $restaurantRepository,
        );

        self::assertSame(
            [
                'refunds' => 2,
                'vendor_requests' => 5,
                'restaurant_moderation' => 1,
            ],
            $extension->getAdminPendingCounts(),
        );

        $functionNames = array_map(
            static fn (TwigFunction $f) => $f->getName(),
            $extension->getFunctions(),
        );
        self::assertContains('admin_pending_counts', $functionNames);
    }
}
