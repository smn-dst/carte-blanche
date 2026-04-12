<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\StatusOrderEnum;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TicketController extends AbstractController
{
    #[Route('/mes-reservations', name: 'app_my_tickets', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $orders = $orderRepository->findBy(
            ['buyer' => $user],
            ['createdAt' => 'DESC']
        );

        $orders = array_values(array_filter($orders, static fn ($o) => \in_array($o->getStatus(), [
            StatusOrderEnum::PAYEE,
            StatusOrderEnum::REMBOURSEE,
            StatusOrderEnum::REMBOURSEMENT_PARTIEL,
        ], true)));

        return $this->render('tickets/index.html.twig', [
            'orders' => $orders,
        ]);
    }
}
