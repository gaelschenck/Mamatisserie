<?php

namespace App\Controller\Admin;

use App\Entity\AboutContent;
use App\Form\AboutContentType;
use App\Repository\AboutContentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/a-propos', name: 'admin_about_')]
#[IsGranted('ROLE_ADMIN')]
class AboutController extends AbstractController
{
    #[Route('', name: 'edit')]
    public function edit(
        Request $request,
        AboutContentRepository $aboutRepository,
        EntityManagerInterface $em,
    ): Response {
        // Singleton : crée l'entrée si elle n'existe pas encore
        $about = $aboutRepository->getSingleton();
        if ($about === null) {
            $about = new AboutContent();
            $em->persist($about);
        }

        $form = $this->createForm(AboutContentType::class, $about);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Page "À propos" mise à jour.');
            return $this->redirectToRoute('admin_about_edit');
        }

        return $this->render('admin/about/edit.html.twig', [
            'form'  => $form,
            'about' => $about,
        ]);
    }
}
