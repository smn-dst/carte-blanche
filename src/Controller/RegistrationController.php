<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\RegistrationManagerService;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly RegistrationManagerService $registrationManagerService,
        private readonly EmailVerifier $emailVerifier,
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
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyEmailRegistration(
        Request $request,
        TranslatorInterface $translator,
        UserRepository $userRepository,
    ): Response {
        // $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $id = $request->query->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        // 3. On retrouve l'utilisateur grâce à cet ID
        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        // 4. On valide le lien de confirmation
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Your email address has been verified. You can now log in.');

        // 5. On redirige vers l'accueil (ou vers ta page de login si tu en as une)
        return $this->redirectToRoute('app_home');
    }
}
