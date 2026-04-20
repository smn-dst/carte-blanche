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
use Symfony\Component\String\Slugger\SluggerInterface;

class VendorRequestController extends AbstractController
{
    // Dossier de stockage des pièces d'identité (hors public pour la sécurité)
    private const ID_CARD_DIRECTORY = 'vendor-id-cards';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly VendorRequestRepository $vendorRequestRepository,
        private readonly SendMailService $sendMailService,
    ) {
    }

    /**
     * Page wizard multi-étapes "Devenir vendeur".
     */
    #[Route('/devenir-vendeur', name: 'app_vendor_request_wizard', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function wizard(): Response
    {
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

        return $this->render('vendor_request/apply.html.twig');
    }

    /**
     * Traitement du formulaire de demande vendeur.
     */
    #[Route('/vendor-request', name: 'app_vendor_request_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, SluggerInterface $slugger): Response
    {
        if (!$this->isCsrfTokenValid('vendor_request', $request->request->getString('_token'))) {
            $this->addFlash('error', 'Token invalide.');

            return $this->redirectToRoute('app_vendor_request_wizard');
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

        $motivation = trim($request->request->getString('motivation'));
        if ('' === $motivation) {
            $this->addFlash('error', 'Merci de renseigner votre motivation.');

            return $this->redirectToRoute('app_vendor_request_wizard');
        }

        $vendorRequest = new VendorRequest();
        $vendorRequest->setUser($user);
        $vendorRequest->setMotivation($motivation);

        $idCardFile = $request->files->get('id_card');
        if (null !== $idCardFile) {
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
            if (!in_array($idCardFile->getMimeType(), $allowedMimes, true)) {
                $this->addFlash('error', 'Format de fichier non accepté. Utilisez JPG, PNG, WebP ou PDF.');

                return $this->redirectToRoute('app_vendor_request_wizard');
            }

            if ($idCardFile->getSize() > 5 * 1024 * 1024) {
                $this->addFlash('error', 'Le fichier ne doit pas dépasser 5 Mo.');

                return $this->redirectToRoute('app_vendor_request_wizard');
            }

            $originalFilename = pathinfo($idCardFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$idCardFile->guessExtension();

            $projectDir = $this->getParameter('kernel.project_dir');
            if (!\is_string($projectDir)) {
                throw new \LogicException('Parameter kernel.project_dir must be a string.');
            }
            $uploadDir = $projectDir.'/var/'.self::ID_CARD_DIRECTORY;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0o750, true);
            }

            $idCardFile->move($uploadDir, $newFilename);
            $vendorRequest->setIdCardFileName($newFilename);
        }

        $this->entityManager->persist($vendorRequest);
        $this->entityManager->flush();

        $this->sendMailService->sendVendorRequestCreated($user);

        $this->addFlash('success', 'Votre demande a bien été envoyée. Vous serez notifié par email dès qu\'une décision sera prise.');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/admin/vendor-requests', name: 'app_admin_vendor_requests', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(): Response
    {
        $pending = $this->vendorRequestRepository->findBy(
            ['status' => StatusVendorRequestEnum::EN_ATTENTE],
            ['createdAt' => 'DESC']
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
        if (!$this->isCsrfTokenValid('vendor_request_action_'.$vendorRequest->getId(), $request->request->getString('_token'))) {
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

        if (null === $user) {
            $this->addFlash('error', 'Utilisateur introuvable.');

            return $this->redirectToRoute('app_admin_vendor_requests');
        }

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
        if (!$this->isCsrfTokenValid('vendor_request_action_'.$vendorRequest->getId(), $request->request->getString('_token'))) {
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

        if (null === $user) {
            $this->addFlash('error', 'Utilisateur introuvable.');

            return $this->redirectToRoute('app_admin_vendor_requests');
        }

        $vendorRequest->setStatus(StatusVendorRequestEnum::REFUSE);
        $vendorRequest->setProcessedAt(new \DateTimeImmutable());
        $vendorRequest->setProcessedBy($admin);

        $this->entityManager->flush();

        $this->sendMailService->sendVendorRequestRefused($user);

        $this->addFlash('success', sprintf('La demande de %s %s a été refusée.', $user->getFirstName(), $user->getLastName()));

        return $this->redirectToRoute('app_admin_vendor_requests');
    }

    /**
     * Téléchargement sécurisé de la pièce d'identité (admin uniquement).
     */
    #[Route('/admin/vendor-requests/{id}/id-card', name: 'app_admin_vendor_request_id_card', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function downloadIdCard(VendorRequest $vendorRequest): Response
    {
        $fileName = $vendorRequest->getIdCardFileName();
        if (null === $fileName) {
            throw $this->createNotFoundException('Aucune pièce d\'identité pour cette demande.');
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        if (!\is_string($projectDir)) {
            throw new \LogicException('Parameter kernel.project_dir must be a string.');
        }
        $filePath = $projectDir.'/var/'.self::ID_CARD_DIRECTORY.'/'.$fileName;
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }

        return $this->file($filePath, 'piece-identite-'.$vendorRequest->getId().'.'.pathinfo($fileName, PATHINFO_EXTENSION));
    }
}
