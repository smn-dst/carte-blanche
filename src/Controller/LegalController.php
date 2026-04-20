<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LegalController extends AbstractController
{
    #[Route('/politique-de-confidentialite', name: 'app_rgpd', methods: ['GET'])]
    public function rgpd(): Response
    {
        return $this->render('legal/rgpd.html.twig');
    }

    #[Route('/conditions-generales-de-vente', name: 'app_cgv', methods: ['GET'])]
    public function cgv(): Response
    {
        return $this->render('legal/cgv.html.twig');
    }

    #[Route('/conditions-generales-utilisation', name: 'app_cgu', methods: ['GET'])]
    public function cgu(): Response
    {
        return $this->render('legal/cgu.html.twig');
    }
}
