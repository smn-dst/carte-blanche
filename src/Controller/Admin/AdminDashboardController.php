<?php

namespace App\Controller\Admin;

use App\Repository\OrderRepository;
use App\Repository\RefundRepository;
use App\Repository\RestaurantRepository;
use App\Repository\UserRepository;
use App\Repository\VendorRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/dashboard')]
#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard', methods: ['GET'])]
    public function index(
        UserRepository $userRepository,
        OrderRepository $orderRepository,
        RefundRepository $refundRepository,
        RestaurantRepository $restaurantRepository,
        VendorRequestRepository $vendorRequestRepository,
    ): Response {
        return $this->render('admin/dashboard/index.html.twig', [
            'users' => $userRepository->getDashboardStats(),
            'orders' => $orderRepository->getDashboardStats(),
            'refundsTotal' => $refundRepository->countAll(),
            'refundsPending' => $refundRepository->countPendingForAdmin(),
            'restaurantsActive' => $restaurantRepository->countActive(),
            'restaurantsPending' => $restaurantRepository->countPendingValidation(),
            'vendorRequestsTotal' => $vendorRequestRepository->countAll(),
            'vendorRequestsPending' => $vendorRequestRepository->countPendingForAdmin(),
        ]);
    }
}
