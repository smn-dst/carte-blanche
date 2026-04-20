<?php

namespace App\Controller\Admin;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/sales')]
#[IsGranted('ROLE_ADMIN')]
class AdminSalesController extends AbstractController
{
    #[Route('', name: 'app_admin_sales', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findCompletedOrders();

        $totalRevenue = array_reduce(
            $orders,
            static fn (float $carry, $order): float => $carry + (float) $order->getTotalAmount(),
            0.0
        );

        return $this->render('admin/sales/index.html.twig', [
            'orders' => $orders,
            'totalRevenue' => $totalRevenue,
            'count' => count($orders),
        ]);
    }
}
