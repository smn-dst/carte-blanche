<?php

namespace App\Controller;

use App\Form\RegistrationFormType;
use App\Service\RegistrationManagerService;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly RegistrationManagerService $registrationManagerService,
    ) {
    }

    #[Route('/register/confirmation', name: 'app_register_confirmation', methods: ['GET'])]
    public function registrationConfirmation(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $email = $request->getSession()->get('registration_pending_email');
        if (!\is_string($email) || '' === $email) {
            return $this->redirectToRoute('app_register');
        }

        return $this->render('registration/confirmation.html.twig', [
            'email' => $email,
        ]);
    }

    /**
     * @throws RandomException
     */
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, Security $security): Response
    {
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $dto = $form->getData();
                $user = $this->registrationManagerService->register($dto);

                $security->login($user, 'form_login', 'main');
                $email = $user->getEmail();
                if (\is_string($email) && '' !== $email) {
                    $request->getSession()->set('registration_pending_email', $email);
                }

                return $this->redirectToRoute('app_register_confirmation');
            }

            // Turbo : une réponse 200 sur POST invalide déclenche « Form responses must redirect »
            return $this->render('registration/register.html.twig', [
                'registrationForm' => $form->createView(),
            ], new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        if ($this->getUser()) {
            $this->addFlash('error', 'Vous êtes déjà connecté !');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
