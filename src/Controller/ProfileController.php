<?php

namespace App\Controller;

use App\Dto\ProfileUpdateInputDto;
use App\Entity\User;
use App\Exception\InvalidCurrentPasswordException;
use App\Exception\ProfileEmailAlreadyUsedException;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Service\ProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    public function __construct(
        private readonly ProfileService $profileService,
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
                $this->profileService->updateProfile($user, $dto);
                $this->addFlash('success', 'Profil mis a jour avec succes.');

                return $this->redirectToRoute('app_profile');
            } catch (ProfileEmailAlreadyUsedException $exception) {
                $form->get('email')->addError(new FormError($exception->getMessage()));
            }
        }

        $changePasswordForm = $this->createForm(ChangePasswordType::class);
        return $this->render('profile/index.html.twig', [
            'profile' => $this->profileService->getProfileViewData($user),
            'user' => $user,
            'form' => $form,
            'changePasswordForm' => $changePasswordForm,
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

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->profileService->changePassword($user, $form->getData());
                $this->addFlash('success', 'Mot de passe modifié avec succès.');
            } catch (InvalidCurrentPasswordException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Formulaire invalide. Veuillez vérifier les champs.');
        }

        return $this->redirectToRoute('app_profile');
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
