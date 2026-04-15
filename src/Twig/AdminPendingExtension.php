<?php

namespace App\Twig;

use App\Repository\RefundRepository;
use App\Repository\RestaurantRepository;
use App\Repository\VendorRequestRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AdminPendingExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
        private readonly RefundRepository $refundRepository,
        private readonly VendorRequestRepository $vendorRequestRepository,
        private readonly RestaurantRepository $restaurantRepository,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('admin_pending_counts', $this->getAdminPendingCounts(...)),
        ];
    }

    /**
     * @return array{refunds: int, vendor_requests: int, restaurant_moderation: int}
     */
    public function getAdminPendingCounts(): array
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return [
                'refunds' => 0,
                'vendor_requests' => 0,
                'restaurant_moderation' => 0,
            ];
        }

        return [
            'refunds' => $this->refundRepository->countPendingForAdmin(),
            'vendor_requests' => $this->vendorRequestRepository->countPendingForAdmin(),
            'restaurant_moderation' => $this->restaurantRepository->countPendingValidation(),
        ];
    }
}
