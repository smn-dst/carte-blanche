<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GithubController extends AbstractController
{
    #[Route('/connect/github', name: 'connect_github')]
    public function connectGithub(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('github')
            ->redirect(['user:email'], []);
    }

    #[Route('/connect/github/check', name: 'connect_github_check')]
    public function checkGithub(): Response
    {
        throw new \LogicException('Cette route est interceptée par le firewall.');
    }
}
