<?php

namespace App\Controller;

use App\Dto\ChangePasswordInputDto;
use App\Dto\ProfileUpdateInputDto;
use App\Entity\User;
use App\Exception\ProfileEmailAlreadyUsedException;
use App\Exception\ProfileWrongCurrentPasswordException;
use App\Form\ChangePasswordFormType;
use App\Form\ProfileType;
use App\Form\UserPreferenceFormType;
use App\Service\ProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
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

        $changePasswordForm = $this->createForm(ChangePasswordFormType::class, new ChangePasswordInputDto());
        $preferencesForm = $this->createForm(UserPreferenceFormType::class, $user->getUserPreferenceEmbedding());

        return $this->renderProfilePage($user, $form, $changePasswordForm, $preferencesForm);
    }

    #[Route('/profil/mot-de-passe', name: 'app_profile_change_password', methods: ['POST'])]
    public function changePassword(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non authentifie.');
        }

        $form = $this->createForm(ChangePasswordFormType::class, new ChangePasswordInputDto());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ChangePasswordInputDto $dto */
            $dto = $form->getData();
            try {
                $this->profileService->changePassword($user, $dto);
                $this->addFlash('success', 'Mot de passe mis a jour avec succes.');

                return $this->redirectToRoute('app_profile');
            } catch (ProfileWrongCurrentPasswordException $e) {
                $form->get('currentPassword')->addError(new FormError($e->getMessage()));
            }
        }

        $profileForm = $this->createForm(ProfileType::class, $this->profileService->createUpdateInputDto($user));
        $preferencesForm = $this->createForm(UserPreferenceFormType::class, $user->getUserPreferenceEmbedding());

        return $this->renderProfilePage($user, $profileForm, $form, $preferencesForm);
    }

    #[Route('/profil/notifications/{key}', name: 'app_profile_notification_toggle', methods: ['POST'])]
    public function notificationToggle(string $key, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non authentifie.');
        }

        $this->profileService->toggleNotification($user, $key);
        $this->addFlash('success', 'Préférence mise à jour.');

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profil/preferences', name: 'app_profile_preferences', methods: ['POST'])]
    public function preferences(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non authentifie.');
        }

        $pref = $user->getUserPreferenceEmbedding();
        $form = $this->createForm(UserPreferenceFormType::class, $pref);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $text = $form->get('preferencesText')->getData() ?? '';
            $this->profileService->updatePreferences($user, $text);
            $this->addFlash('success', 'Préférences enregistrées.');
        }

        return $this->redirectToRoute('app_profile');
    }

    private function renderProfilePage(
        User $user,
        FormInterface $profileForm,
        FormInterface $changePasswordForm,
        FormInterface $preferencesForm,
    ): Response {
        return $this->render('profile/index.html.twig', [
            'profile' => $this->profileService->getProfileViewData($user),
            'user' => $user,
            'form' => $profileForm,
            'changePasswordForm' => $changePasswordForm,
            'preferencesForm' => $preferencesForm,
        ]);
    }
}
