<?php

namespace App\Controller;

use App\Dto\ProfileUpdateInputDto;
use App\Entity\User;
use App\Form\ProfileType;
use App\Service\EmailChangeService;
use App\Service\PasswordResetService;
use App\Service\ProfileService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    public function __construct(
        private readonly ProfileService $profileService,
        private readonly PasswordResetService $passwordResetService,
        private readonly EmailChangeService $emailChangeService,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/profil', name: 'app_profile', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non authentifie.');
        }

        $form = $this->createForm(ProfileType::class, $this->profileService->createUpdateInputDto($user));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ProfileUpdateInputDto $dto */
            $dto = $form->getData();
            try {
                $this->emailChangeService->requestEmailChange($user, $dto->email);
                $this->addFlash('success', 'Un email de confirmation a été envoyé à votre nouvelle adresse.');

                return $this->redirectToRoute('app_profile');
            } catch (\InvalidArgumentException $exception) {
                $this->addFlash('error', $exception->getMessage());
            } catch (\Throwable $exception) {
                $this->logger->error('ProfileController: email change request failed for user {id}: {message}', [
                    'id' => $user->getId(),
                    'message' => $exception->getMessage(),
                ]);
                $this->addFlash('error', "Impossible d'envoyer l'e-mail de confirmation pour le moment.");
            }
        }

        return $this->render('profile/index.html.twig', [
            'profile' => $this->profileService->getProfileViewData($user),
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/profil/changer-mot-de-passe', name: 'app_profile_change_password', methods: ['POST'])]
    public function changePassword(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $csrfToken = $request->request->getString('_token');
        if (!$this->isCsrfTokenValid('profile_password_reset', '' !== $csrfToken ? $csrfToken : null)) {
            $this->addFlash('error', 'Action invalide.');

            return $this->redirectToRoute('app_profile');
        }

        $email = $user->getEmail();
        if (null === $email || '' === trim($email)) {
            $this->addFlash('error', 'Aucune adresse e-mail associée à ce compte.');

            return $this->redirectToRoute('app_profile');
        }

        try {
            $this->passwordResetService->requestReset($email);
            $this->addFlash('success', "Un email vous a été envoyé à l'adresse mail associée.");
        } catch (\Throwable $e) {
            $this->logger->error('ProfileController: password reset email failed for user {id} ({email}): {message}', [
                'id' => $user->getId(),
                'email' => $email,
                'message' => $e->getMessage(),
            ]);
            $this->addFlash('error', 'Impossible d’envoyer l’e-mail pour le moment. Réessayez plus tard.');
        }

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profil/changer-email/{token}', name: 'app_profile_confirm_email_change', methods: ['GET'])]
    public function confirmEmailChange(string $token): Response
    {
        try {
            $this->emailChangeService->confirmEmailChange($token);
            $this->addFlash('success', 'Votre adresse email a été mise à jour.');
        } catch (\RuntimeException $exception) {
            $this->addFlash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            $this->logger->error('ProfileController: email change confirmation failed: {message}', [
                'message' => $exception->getMessage(),
            ]);
            $this->addFlash('error', "Impossible de valider le changement d'email.");
        }

        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_profile');
        }

        return $this->redirectToRoute('app_login');
    }

    #[Route('/profil/supprimer', name: 'app_profile_delete_confirm', methods: ['GET'])]
    public function deleteConfirm(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('profile/delete_confirm.html.twig');
    }

    #[Route('/profil/supprimer', name: 'app_profile_delete', methods: ['POST'])]
    public function delete(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $csrfToken = $request->request->getString('_token');

        if (!$this->isCsrfTokenValid('delete_account', '' !== $csrfToken ? $csrfToken : null)) {
            $this->addFlash('error', 'Token invalide.');

            return $this->redirectToRoute('app_profile_delete_confirm');
        }

        $this->profileService->deleteAccount($user);
        $request->getSession()->invalidate();

        return $this->redirectToRoute('app_home');
    }
}
