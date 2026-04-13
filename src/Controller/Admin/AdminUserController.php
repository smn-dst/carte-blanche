<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'app_admin_users', methods: ['GET'])]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        $role = $request->query->getString('role');
        $search = $request->query->getString('search');

        $users = $userRepository->findForAdmin($role ?: null, $search ?: null);

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'currentRole' => $role,
            'currentSearch' => $search,
            'total' => count($users),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_user_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(User $user, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('admin_delete_user_'.$user->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_admin_users');
        }

        // Soft-delete : on anonymise le compte au lieu de le supprimer physiquement
        // (évite les contraintes FK sur orders, refunds, etc.)
        $user->setEmail('deleted_'.$user->getId().'@deleted.local');
        $user->setFirstName('[Supprimé]');
        $user->setLastName('');
        $user->setPhoneNumber(null);
        $user->setPassword('');
        $user->setRoles([]);
        $user->setIsSuspended(true);

        $this->em->flush();

        $this->addFlash('success', 'Compte supprimé avec succès.');

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/{id}/toggle-suspend', name: 'app_admin_user_toggle_suspend', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleSuspend(User $user, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('admin_suspend_user_'.$user->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_admin_users');
        }

        $user->setIsSuspended(!($user->isSuspended() ?? false));
        $this->em->flush();

        $label = ($user->isSuspended() ?? false) ? 'suspendu' : 'réactivé';
        $this->addFlash('success', sprintf('Compte %s %s.', $user->getFirstName(), $label));

        return $this->redirectToRoute('app_admin_users');
    }
}
