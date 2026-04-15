<?php

namespace App\Controller\Admin;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\StatusRestaurantEnum;
use App\Repository\RestaurantRepository;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/restaurants')]
#[IsGranted('ROLE_ADMIN')]
class AdminRestaurantController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SendMailService $sendMailService,
    ) {
    }

    #[Route('', name: 'app_admin_restaurants', methods: ['GET'])]
    public function index(RestaurantRepository $restaurantRepository): Response
    {
        $pending = $restaurantRepository->findPendingValidation();
        $recent = $restaurantRepository->findRecentlyProcessed();

        return $this->render('admin/restaurants/index.html.twig', [
            'pending' => $pending,
            'recent' => $recent,
        ]);
    }

    #[Route('/{id}/approve', name: 'app_admin_restaurant_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function approve(Restaurant $restaurant, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('admin_restaurant_'.$restaurant->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_admin_restaurants');
        }

        if (StatusRestaurantEnum::EN_MODERATION !== $restaurant->getStatus()) {
            $this->addFlash('error', 'Ce restaurant n\'est pas en attente de validation.');

            return $this->redirectToRoute('app_admin_restaurants');
        }

        // Si une date d'enchère est définie → PROGRAMME, sinon → PUBLIE
        $newStatus = $restaurant->getAuctionDate()
            ? StatusRestaurantEnum::PROGRAMME
            : StatusRestaurantEnum::PUBLIE;

        $restaurant->setStatus($newStatus);
        $restaurant->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        $owner = $restaurant->getOwner();
        if ($owner instanceof User) {
            $this->sendMailService->sendRestaurantApproved($owner, $restaurant);
        }

        $this->addFlash('success', sprintf(
            '"%s" a été approuvé et est maintenant %s.',
            $restaurant->getName(),
            StatusRestaurantEnum::PROGRAMME === $newStatus ? 'programmé' : 'publié'
        ));

        return $this->redirectToRoute('app_admin_restaurants');
    }

    #[Route('/{id}/reject', name: 'app_admin_restaurant_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reject(Restaurant $restaurant, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('admin_restaurant_'.$restaurant->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_admin_restaurants');
        }

        if (StatusRestaurantEnum::EN_MODERATION !== $restaurant->getStatus()) {
            $this->addFlash('error', 'Ce restaurant n\'est pas en attente de validation.');

            return $this->redirectToRoute('app_admin_restaurants');
        }

        // On remet en brouillon : le vendeur peut corriger et re-soumettre
        $restaurant->setStatus(StatusRestaurantEnum::BROUILLON);
        $restaurant->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        $owner = $restaurant->getOwner();
        if ($owner instanceof User) {
            $this->sendMailService->sendRestaurantRejected($owner, $restaurant);
        }

        $this->addFlash('success', sprintf(
            '"%s" a été refusé et renvoyé en brouillon. Le vendeur a été notifié.',
            $restaurant->getName()
        ));

        return $this->redirectToRoute('app_admin_restaurants');
    }
}
