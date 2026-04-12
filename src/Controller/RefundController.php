<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\RefundRepository;
use App\Service\RefundService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RefundController extends AbstractController
{
    public function __construct(
        private readonly RefundService $refundService,
    ) {
    }

    /**
     * Acheteur : formulaire de demande de remboursement.
     */
    #[Route('/mes-reservations/{id}/remboursement', name: 'app_refund_request', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function request(int $id, Request $request, OrderRepository $orderRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $order = $orderRepository->find($id);
        if (null === $order || $order->getBuyer()?->getId() !== $user->getId()) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        if ('POST' === $request->getMethod()) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('refund_request_'.$id, \is_string($token) ? $token : null)) {
                return $this->redirectToRoute('app_my_tickets');
            }

            $reason = $request->request->get('reason', '');
            if ('' === trim((string) $reason)) {
                $this->addFlash('error', 'Veuillez indiquer un motif.');

                return $this->redirectToRoute('app_refund_request', ['id' => $id]);
            }

            try {
                $this->refundService->requestRefund($user, $order, trim((string) $reason));
                $this->addFlash('success', 'Votre demande de remboursement a été envoyée. Elle sera traitée sous 48h.');
            } catch (\LogicException $e) {
                $this->addFlash('error', $e->getMessage());
            }

            return $this->redirectToRoute('app_my_tickets');
        }

        return $this->render('refund/request.html.twig', [
            'order' => $order,
        ]);
    }

    /**
     * Acheteur : voir ses demandes de remboursement.
     */
    #[Route('/mes-remboursements', name: 'app_my_refunds', methods: ['GET'])]
    public function myRefunds(RefundRepository $refundRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $refunds = $refundRepository->findBy(
            ['requestedBy' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('refund/my_refunds.html.twig', [
            'refunds' => $refunds,
        ]);
    }
}
