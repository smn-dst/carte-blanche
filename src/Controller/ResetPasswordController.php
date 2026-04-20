<?php

namespace App\Controller;

use App\Dto\ResetPasswordInputDto;
use App\Form\ResetPasswordFormType;
use App\Service\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ResetPasswordController extends AbstractController
{
    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function reset(
        string $token,
        Request $request,
        PasswordResetService $passwordResetService,
    ): Response {
        try {
            $passwordResetService->tokenExists($token);
        } catch (\RuntimeException) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');

            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                /** @var ResetPasswordInputDto $data */
                $data = $form->getData();
                try {
                    $passwordResetService->resetPassword($token, $data->plainPassword);
                } catch (\RuntimeException) {
                    $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');

                    return $this->redirectToRoute('app_forgot_password');
                }

                $this->addFlash('success', 'Votre mot de passe a été mis à jour. Vous pouvez vous connecter.');

                return $this->redirectToRoute('app_login');
            }

            return $this->render('reset_password/index.html.twig', [
                'resetForm' => $form,
            ], new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return $this->render('reset_password/index.html.twig', [
            'resetForm' => $form,
        ]);
    }
}
