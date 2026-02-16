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

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var RequestPasswordInputDto $dto */
            $dto = $form->getData();
            try {
                $passwordResetService->requestReset($dto->email);
            } catch (\Throwable) {
                $this->addFlash('error', 'Unable to send reset instructions right now. Please try again.');

                return $this->redirectToRoute('app_forgot_password');
            }
            $this->addFlash('success', 'If an account with that email exists, a password reset link has been sent.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('forgot_password/index.html.twig', [
            'requestForm' => $form,
        ]);
    }
}
