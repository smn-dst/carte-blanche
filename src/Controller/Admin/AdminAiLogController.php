<?php

namespace App\Controller\Admin;

use App\Repository\AiLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin-logs')]
#[IsGranted('ROLE_ADMIN')]
class AdminAiLogController extends AbstractController
{
    #[Route('', name: 'app_admin_ai_logs', methods: ['GET'])]
    public function index(AiLogRepository $aiLogRepository): Response
    {
        $logs = $aiLogRepository->findBy([], ['createdAt' => 'DESC'], 200);

        $stats = [
            'total' => count($logs),
            'chatbot' => count(array_filter($logs, fn ($l) => 'chatbot' === $l->getType())),
            'description' => count(array_filter($logs, fn ($l) => 'description' === $l->getType())),
            'recommendation' => count(array_filter($logs, fn ($l) => 'recommendation' === $l->getType())),
        ];

        return $this->render('admin/ai_logs/index.html.twig', [
            'logs' => $logs,
            'stats' => $stats,
        ]);
    }
}
