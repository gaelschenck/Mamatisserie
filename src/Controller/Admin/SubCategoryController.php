<?php

namespace App\Controller\Admin;

use App\Entity\SubCategory;
use App\Form\SubCategoryType;
use App\Repository\SubCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/sous-categories', name: 'admin_subcategory_')]
#[IsGranted('ROLE_ADMIN')]
class SubCategoryController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(SubCategoryRepository $subCategoryRepository): Response
    {
        return $this->render('admin/subcategory/index.html.twig', [
            'subCategories' => $subCategoryRepository->findBy([], ['category' => 'ASC', 'position' => 'ASC']),
        ]);
    }

    #[Route('/nouvelle', name: 'new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
    ): Response {
        $subCategory = new SubCategory();
        $form = $this->createForm(SubCategoryType::class, $subCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $subCategory->setSlug(strtolower($slugger->slug($subCategory->getName())));
            $em->persist($subCategory);
            $em->flush();

            $this->addFlash('success', sprintf('Sous-catégorie "%s" créée.', $subCategory->getName()));
            return $this->redirectToRoute('admin_subcategory_index');
        }

        return $this->render('admin/subcategory/form.html.twig', [
            'form'        => $form,
            'subCategory' => $subCategory,
            'isNew'       => true,
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(
        SubCategory $subCategory,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
    ): Response {
        $form = $this->createForm(SubCategoryType::class, $subCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $subCategory->setSlug(strtolower($slugger->slug($subCategory->getName())));
            $em->flush();

            $this->addFlash('success', sprintf('Sous-catégorie "%s" mise à jour.', $subCategory->getName()));
            return $this->redirectToRoute('admin_subcategory_index');
        }

        return $this->render('admin/subcategory/form.html.twig', [
            'form'        => $form,
            'subCategory' => $subCategory,
            'isNew'       => false,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        SubCategory $subCategory,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_subcategory_' . $subCategory->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_subcategory_index');
        }

        $name = $subCategory->getName();
        $em->remove($subCategory);
        $em->flush();

        $this->addFlash('success', sprintf('Sous-catégorie "%s" supprimée.', $name));
        return $this->redirectToRoute('admin_subcategory_index');
    }
}
