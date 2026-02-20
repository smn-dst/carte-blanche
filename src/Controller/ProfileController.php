<?php

namespace App\Controller;

use App\Dto\ProfileUpdateInputDto;
use App\Entity\User;
use App\Exception\ProfileEmailAlreadyUsedException;
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

        return $this->render('profile/index.html.twig', [
            'profile' => $this->profileService->getProfileViewData($user),
            'user' => $user,
            'form' => $form,
        ]);
    }
}
