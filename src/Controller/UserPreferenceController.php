<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserPreferenceEmbedding;
use App\Form\UserPreferenceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/register')]
class UserPreferenceController extends AbstractController
{
    #[Route('/preferences', name: 'app_register_preferences', methods: ['GET', 'POST'])]
    public function preferences(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(UserPreferenceType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $preferenceText = $this->buildPreferenceText($data);

            $embedding = $user->getUserPreferenceEmbedding() ?? new UserPreferenceEmbedding();
            $embedding->setUser($user);
            $embedding->setPreferencesText($preferenceText);
            $embedding->setEmbedding([]);
            $embedding->setUpdatedAt(new \DateTimeImmutable());

            $em->persist($embedding);
            $em->flush();

            $this->addFlash('success', 'Vos préférences ont bien été enregistrées !');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/userPreference.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/preferences/save', name: 'app_register_preferences_save', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function save(
        Request $request,
        EntityManagerInterface $em,
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        $preferenceText = $this->buildPreferenceText($data);

        $embedding = $user->getUserPreferenceEmbedding() ?? new UserPreferenceEmbedding();
        $embedding->setUser($user);
        $embedding->setPreferencesText($preferenceText);
        $embedding->setEmbedding([]);
        $embedding->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($embedding);
        $em->flush();

        $this->addFlash('success', 'Vos préférences ont bien été enregistrées !');
        return $this->json([
            'success' => true,
            'redirect' => $this->generateUrl('app_home'),
        ]);
    }

    #[Route('/preferences/skip', name: 'app_register_preferences_skip', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function skip(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'redirect' => $this->generateUrl('app_home'),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildPreferenceText(array $data): string
    {
        $parts = [];

        if (!empty($data['cuisineTypes'])) {
            $cuisines = is_array($data['cuisineTypes']) ? implode(', ', $data['cuisineTypes']) : $data['cuisineTypes'];
            $parts[] = "Types de cuisine préférés : {$cuisines}";
        }

        $budgetMin = $data['budgetMin'] ?? null;
        $budgetMax = $data['budgetMax'] ?? null;
        if ($budgetMin || $budgetMax) {
            $min = $budgetMin ? number_format((float) $budgetMin, 0, ',', ' ') . ' €' : '0 €';
            $max = $budgetMax ? number_format((float) $budgetMax, 0, ',', ' ') . ' €' : 'illimité';
            $parts[] = "Budget recherché : entre {$min} et {$max}";
        }

        if (!empty($data['preferredCity'])) {
            $city = $data['preferredCity'];
            $radius = $data['searchRadius'] ?? 0;
            $radiusText = $radius ? "dans un rayon de {$radius} km" : 'sur toute la France';
            $parts[] = "Localisation : {$city}, {$radiusText}";
        }

        if (!empty($data['capacityMin']) && $data['capacityMin'] > 0) {
            $parts[] = "Capacité minimum souhaitée : {$data['capacityMin']} couverts";
        }

        return implode('. ', $parts);
    }
}
