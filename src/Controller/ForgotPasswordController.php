<?php

namespace App\Controller;

use App\Dto\RequestPasswordInputDto;
use App\Form\ForgotPasswordRequestFormType;
use App\Service\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ForgotPasswordController extends AbstractController
{
    #[Route('/forgot/password', name: 'app_forgot_password')]
    public function request(
        Request $request,
        PasswordResetService $passwordResetService,
    ): Response {
        $form = $this->createForm(ForgotPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                /** @var RequestPasswordInputDto $dto */
                $dto = $form->getData();
                try {
                    $passwordResetService->requestReset($dto->email);
                } catch (\Throwable) {
                    $this->addFlash('error', 'Impossible d’envoyer l’e-mail pour le moment. Réessayez plus tard.');

                    return $this->redirectToRoute('app_forgot_password');
                }
                $this->addFlash('success', 'Si un compte existe avec cette adresse, un lien de réinitialisation vient d’être envoyé.');

                return $this->redirectToRoute('app_forgot_password');
            }

            return $this->render('forgot_password/index.html.twig', [
                'requestForm' => $form,
            ], new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return $this->render('forgot_password/index.html.twig', [
            'requestForm' => $form,
        ]);
    }
}
