<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserEditType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Option B : tout admin peut modifier les 2 comptes (username + email uniquement).
 * Le mot de passe est géré via la page "Mot de passe oublié".
 */
#[Route('/admin/comptes', name: 'admin_user_')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
    ): Response {
        $form = $this->createForm(UserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Unicité username
            $existingByUsername = $userRepository->findOneBy(['username' => $user->getUsername()]);
            if ($existingByUsername !== null && $existingByUsername->getId() !== $user->getId()) {
                $this->addFlash('error', 'Ce nom d\'utilisateur est déjà utilisé.');
                return $this->render('admin/user/edit.html.twig', ['form' => $form, 'user' => $user]);
            }
            // Unicité email
            $existingByEmail = $userRepository->findOneBy(['email' => $user->getEmail()]);
            if ($existingByEmail !== null && $existingByEmail->getId() !== $user->getId()) {
                $this->addFlash('error', 'Cette adresse e-mail est déjà utilisée.');
                return $this->render('admin/user/edit.html.twig', ['form' => $form, 'user' => $user]);
            }

            $em->flush();
            $this->addFlash('success', sprintf('Compte "%s" mis à jour.', $user->getUsername()));
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }
}
