<?php

namespace App\Controller;

use App\Enum\StatusTicketEnum;
use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TicketController extends AbstractController
{
    #[Route('/tickets', name: 'app_tickets', methods: ['GET'])]
    public function index(TicketRepository $ticketRepository, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $statusParam = $request->query->get('status', 'all');
        $statusFilter = null;

        if ('all' !== $statusParam) {
            $statusFilter = StatusTicketEnum::tryFrom($statusParam);
            if (null === $statusFilter) {
                $statusParam = 'all';
            }
        }

        $tickets = $ticketRepository->findByUser($user, $statusFilter);

        return $this->render('tickets/index.html.twig', [
            'tickets' => $tickets,
            'status' => $statusParam,
        ]);
    }
}
