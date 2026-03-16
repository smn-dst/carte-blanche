<?php

namespace App\Controller;

use App\Dto\RestaurantStep4Dto;
use App\Entity\User;
use App\Exception\RestaurantNotFoundException;
use App\Form\RestaurantStep1FormType;
use App\Form\RestaurantStep2FormType;
use App\Form\RestaurantStep3FormType;
use App\Form\RestaurantStep4FormType;
use App\Repository\RestaurantRepository;
use App\Security\Voter\RestaurantVoter;
use App\Service\RestaurantService;
use App\Service\RestaurantWizardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RestaurantController extends AbstractController
{
    public function __construct(
        private readonly RestaurantService $restaurantService,
        private readonly RestaurantWizardService $wizardService,
    ) {
    }

    #[Route('/restaurant/{id}', name: 'app_restaurant_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, RestaurantRepository $restaurantRepository): Response
    {
        $restaurant = $restaurantRepository->find($id);
        if (null === $restaurant) {
            throw $this->createNotFoundException('Restaurant non trouvé.');
        }

        return $this->render('encheres/detail.html.twig', [
            'restaurant' => $restaurant,
        ]);
    }

    #[Route('/mes-restaurants', name: 'app_restaurant_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!in_array('ROLE_VENDOR', $user->getRoles(), true)) {
            return $this->redirect('/');
        }

        return $this->render('restaurant/index.html.twig', [
            'restaurants' => $this->restaurantService->findByOwner($user),
        ]);
    }

    #[Route('/restaurant/nouveau', name: 'app_restaurant_new', methods: ['GET'])]
    public function new(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->redirectToRoute('app_restaurant_new_step', ['step' => 1]);
    }

    #[Route('/restaurant/nouveau/{step}', name: 'app_restaurant_new_step', requirements: ['step' => '[1-4]'], methods: ['GET', 'POST'])]
    public function newStep(int $step, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('GET') && !$this->wizardService->isStepAccessible($step)) {
            return $this->redirectToRoute('app_restaurant_new_step', ['step' => 1]);
        }

        [$formClass, $dto] = $this->resolveStepForm($step, null);
        $form = $this->createForm($formClass, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $submittedDto = $form->getData();

            if ($step < 4) {
                if (!is_object($submittedDto)) {
                    throw new \LogicException('Le DTO du wizard doit être un objet.');
                }

                $this->wizardService->saveStep($step, $submittedDto);

                return $this->redirectToRoute('app_restaurant_new_step', ['step' => $step + 1]);
            }

            if (!$submittedDto instanceof RestaurantStep4Dto) {
                throw new \LogicException('Le step 4 du wizard doit fournir un RestaurantStep4Dto.');
            }

            $fullDto = $this->wizardService->buildFullDto($submittedDto);
            $restaurant = $this->restaurantService->create($user, $fullDto);
            $this->wizardService->clearWizardSession();
            $this->addFlash('success', 'Restaurant créé avec succès.');

            return $this->redirectToRoute('app_restaurant_edit_step', ['id' => $restaurant->getId(), 'step' => 1]);
        }

        return $this->render('restaurant/wizard_new.html.twig', [
            'form' => $form,
            'step' => $step,
            'googleMapsApiKey' => $this->resolveGoogleMapsApiKey(),
        ]);
    }

    #[Route('/restaurant/{id}/modifier', name: 'app_restaurant_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function edit(int $id): Response
    {
        try {
            $restaurant = $this->restaurantService->findOrFail($id);
        } catch (RestaurantNotFoundException) {
            throw $this->createNotFoundException('Restaurant non trouvé.');
        }

        $this->denyAccessUnlessGranted(RestaurantVoter::EDIT, $restaurant);

        return $this->redirectToRoute('app_restaurant_edit_step', ['id' => $id, 'step' => 1]);
    }

    #[Route('/restaurant/{id}/modifier/{step}', name: 'app_restaurant_edit_step', requirements: ['id' => '\d+', 'step' => '[1-4]'], methods: ['GET', 'POST'])]
    public function editStep(int $id, int $step, Request $request): Response
    {
        try {
            $restaurant = $this->restaurantService->findOrFail($id);
        } catch (RestaurantNotFoundException) {
            throw $this->createNotFoundException('Restaurant non trouvé.');
        }

        $this->denyAccessUnlessGranted(RestaurantVoter::EDIT, $restaurant);

        [$formClass, $dto] = $this->resolveStepForm($step, $restaurant);
        $form = $this->createForm($formClass, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->restaurantService->updateStep($restaurant, $dto);
            $this->addFlash('success', 'Étape mise à jour.');

            if ($step < 4) {
                return $this->redirectToRoute('app_restaurant_edit_step', ['id' => $id, 'step' => $step + 1]);
            }

            return $this->redirectToRoute('app_restaurant_edit_step', ['id' => $id, 'step' => 4]);
        }

        return $this->render('restaurant/wizard_edit.html.twig', [
            'form' => $form,
            'restaurant' => $restaurant,
            'step' => $step,
            'googleMapsApiKey' => $this->resolveGoogleMapsApiKey(),
        ]);
    }

    #[Route('/restaurant/{id}/supprimer', name: 'app_restaurant_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        try {
            $restaurant = $this->restaurantService->findOrFail($id);
        } catch (RestaurantNotFoundException) {
            throw $this->createNotFoundException('Restaurant non trouvé.');
        }

        $this->denyAccessUnlessGranted(RestaurantVoter::DELETE, $restaurant);

        $csrfToken = (string) ($request->request->get('_token') ?? $request->getPayload()->getString('_token'));
        if (!$this->isCsrfTokenValid('delete_restaurant_'.$id, $csrfToken)) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_restaurant_index');
        }

        $this->restaurantService->delete($restaurant);
        $this->addFlash('success', 'Restaurant supprimé.');

        return $this->redirectToRoute('app_restaurant_index');
    }

    #[Route('/restaurant/{restaurantId}/image/{imageId}/supprimer', name: 'app_restaurant_image_delete', requirements: ['restaurantId' => '\d+', 'imageId' => '\d+'], methods: ['POST'])]
    public function deleteImage(int $restaurantId, int $imageId, Request $request): Response
    {
        try {
            $restaurant = $this->restaurantService->findOrFail($restaurantId);
        } catch (RestaurantNotFoundException) {
            throw $this->createNotFoundException('Restaurant non trouvé.');
        }

        $this->denyAccessUnlessGranted(RestaurantVoter::EDIT, $restaurant);

        $csrfToken = (string) ($request->request->get('_token') ?? $request->getPayload()->getString('_token'));
        if (!$this->isCsrfTokenValid('delete_image_'.$imageId, $csrfToken)) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_restaurant_edit_step', ['id' => $restaurantId, 'step' => 4]);
        }

        $this->restaurantService->deleteImage($restaurantId, $imageId);
        $this->addFlash('success', 'Image supprimée.');

        return $this->redirectToRoute('app_restaurant_edit_step', ['id' => $restaurantId, 'step' => 4]);
    }

    /**
     * @return array{0: class-string, 1: object}
     */
    private function resolveStepForm(int $step, ?\App\Entity\Restaurant $restaurant): array
    {
        $dto = null !== $restaurant
            ? $this->restaurantService->buildInputDtoForStep($restaurant, $step)
            : match ($step) {
                1 => $this->wizardService->getStep1Dto(),
                2 => $this->wizardService->getStep2Dto(),
                3 => $this->wizardService->getStep3Dto(),
                4 => $this->wizardService->getStep4Dto(),
                default => throw new \InvalidArgumentException("Step {$step} invalide."),
            };

        $formClass = match ($step) {
            1 => RestaurantStep1FormType::class,
            2 => RestaurantStep2FormType::class,
            3 => RestaurantStep3FormType::class,
            4 => RestaurantStep4FormType::class,
            default => throw new \InvalidArgumentException("Step {$step} invalide."),
        };

        return [$formClass, $dto];
    }

    private function resolveGoogleMapsApiKey(): string
    {
        $apiKey = $_SERVER['GOOGLE_MAPS_API_KEY'] ?? $_ENV['GOOGLE_MAPS_API_KEY'] ?? null;
        if (!is_string($apiKey)) {
            return '';
        }

        return trim($apiKey);
    }
}
