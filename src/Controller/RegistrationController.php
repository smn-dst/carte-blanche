<?php

namespace App\Controller;

use App\Form\RegistrationFormType;
use App\Service\RegistrationManagerService;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly RegistrationManagerService $registrationManagerService,
    ) {
    }

    /**
     * @throws RandomException
     */
    #[Route('/register', name: 'app_register')]
    public function register(Request $request,
    ): Response {
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get the data from the form, which will be an instance of RegistrationInputDto
            $dto = $form->getData();
            // Call the registration manager service to handle the registration logic
            $this->registrationManagerService->register($dto);

            // Add a flash message to inform the user that registration was successful
            $this->addFlash('success', 'Registration successful! Please check your email to verify your account.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
