<?php

namespace App\Controller\Admin;

use App\Entity\Refund;
use App\Entity\User;
use App\Repository\RefundRepository;
use App\Service\RefundService;
use App\Service\SendMailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/refunds')]
#[IsGranted('ROLE_ADMIN')]
class AdminRefundController extends AbstractController
{
    public function __construct(
        private readonly RefundService $refundService,
        private readonly SendMailService $sendMailService,
    ) {
    }

    #[Route('', name: 'app_admin_refunds', methods: ['GET'])]
    public function index(RefundRepository $refundRepository): Response
    {
        $pending = $refundRepository->findPendingForAdmin();
        $processed = $refundRepository->findProcessedForAdmin();

        return $this->render('admin/refunds/index.html.twig', [
            'pending' => $pending,
            'processed' => $processed,
        ]);
    }

    #[Route('/{id}/approve', name: 'app_admin_refund_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function approve(Refund $refund, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('admin_refund_'.$refund->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_admin_refunds');
        }

        /** @var User $admin */
        $admin = $this->getUser();

        try {
            $this->refundService->approveRefund($refund, $admin);

            $buyer = $refund->getOrder()?->getBuyer();
            if ($buyer instanceof User) {
                $this->sendMailService->sendRefundApproved($buyer, $refund);
            }

            $this->addFlash('success', sprintf(
                'Remboursement de %s € approuvé et traité via Stripe.',
                number_format((float) $refund->getAmount(), 0, ',', ' ')
            ));
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur Stripe : '.$e->getMessage());
        }

        return $this->redirectToRoute('app_admin_refunds');
    }

    #[Route('/{id}/reject', name: 'app_admin_refund_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reject(Refund $refund, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('admin_refund_'.$refund->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_admin_refunds');
        }

        /** @var User $admin */
        $admin = $this->getUser();

        try {
            $this->refundService->rejectRefund($refund, $admin);

            $buyer = $refund->getOrder()?->getBuyer();
            if ($buyer instanceof User) {
                $this->sendMailService->sendRefundRejected($buyer, $refund);
            }

            $this->addFlash('success', 'Demande de remboursement refusée. L\'utilisateur a été notifié.');
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_refunds');
    }
}
