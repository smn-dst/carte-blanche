<?php

namespace App\Controller;

use App\Entity\VendorRequest;
use App\Enum\StatusVendorRequestEnum;
use App\Repository\VendorRequestRepository;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class VendorRequestController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly VendorRequestRepository $vendorRequestRepository,
        private readonly SendMailService $sendMailService,
    ) {
    }

    #[Route('/vendor-request', name: 'app_vendor_request_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('vendor_request', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');

            return $this->redirectToRoute('app_home');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_VENDOR')) {
            $this->addFlash('info', 'Vous êtes déjà vendeur.');

            return $this->redirectToRoute('app_home');
        }

        if ($this->vendorRequestRepository->hasPendingRequest($user)) {
            $this->addFlash('info', 'Vous avez déjà une demande en cours d\'examen.');

            return $this->redirectToRoute('app_home');
        }

        $vendorRequest = new VendorRequest();
        $vendorRequest->setUser($user);

        $this->entityManager->persist($vendorRequest);
        $this->entityManager->flush();

        $this->sendMailService->sendVendorRequestCreated($user);

        $this->addFlash('success', 'Votre demande pour devenir vendeur a bien été envoyée. Vous serez notifié par email.');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/admin/vendor-requests', name: 'app_admin_vendor_requests', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(): Response
    {
        $pending = $this->vendorRequestRepository->findBy(
            ['status' => StatusVendorRequestEnum::EN_ATTENTE],
            ['createdAt' => 'ASC']
        );

        $processed = $this->vendorRequestRepository->findBy(
            ['status' => [StatusVendorRequestEnum::APPROUVE, StatusVendorRequestEnum::REFUSE]],
            ['processedAt' => 'DESC'],
            20
        );

        return $this->render('admin/vendor_requests/index.html.twig', [
            'pending' => $pending,
            'processed' => $processed,
        ]);
    }

    #[Route('/admin/vendor-requests/{id}/approve', name: 'app_admin_vendor_request_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approve(VendorRequest $vendorRequest, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('vendor_request_action_'.$vendorRequest->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');

            return $this->redirectToRoute('app_admin_vendor_requests');
        }

        if (StatusVendorRequestEnum::EN_ATTENTE !== $vendorRequest->getStatus()) {
            $this->addFlash('error', 'Cette demande a déjà été traitée.');

            return $this->redirectToRoute('app_admin_vendor_requests');
        }

        /** @var \App\Entity\User $admin */
        $admin = $this->getUser();
        $user = $vendorRequest->getUser();

        $vendorRequest->setStatus(StatusVendorRequestEnum::APPROUVE);
        $vendorRequest->setProcessedAt(new \DateTimeImmutable());
        $vendorRequest->setProcessedBy($admin);

        $user->setRoles(['ROLE_VENDOR']);

        $this->entityManager->flush();

        $this->sendMailService->sendVendorRequestApproved($user);

        $this->addFlash('success', sprintf('La demande de %s %s a été approuvée.', $user->getFirstName(), $user->getLastName()));

        return $this->redirectToRoute('app_admin_vendor_requests');
    }

    #[Route('/admin/vendor-requests/{id}/refuse', name: 'app_admin_vendor_request_refuse', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function refuse(VendorRequest $vendorRequest, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('vendor_request_action_'.$vendorRequest->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');

            return $this->redirectToRoute('app_admin_vendor_requests');
        }

        if (StatusVendorRequestEnum::EN_ATTENTE !== $vendorRequest->getStatus()) {
            $this->addFlash('error', 'Cette demande a déjà été traitée.');

            return $this->redirectToRoute('app_admin_vendor_requests');
        }

        /** @var \App\Entity\User $admin */
        $admin = $this->getUser();
        $user = $vendorRequest->getUser();

        $vendorRequest->setStatus(StatusVendorRequestEnum::REFUSE);
        $vendorRequest->setProcessedAt(new \DateTimeImmutable());
        $vendorRequest->setProcessedBy($admin);

        $this->entityManager->flush();

        $this->sendMailService->sendVendorRequestRefused($user);

        $this->addFlash('success', sprintf('La demande de %s %s a été refusée.', $user->getFirstName(), $user->getLastName()));

        return $this->redirectToRoute('app_admin_vendor_requests');
    }
}
