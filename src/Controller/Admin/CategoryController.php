<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/categories', name: 'admin_category_')]
#[IsGranted('ROLE_ADMIN')]
class CategoryController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('admin/category/index.html.twig', [
            'categories' => $categoryRepository->findAllOrdered(),
        ]);
    }

    #[Route('/nouvelle', name: 'new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
    ): Response {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->setSlug(strtolower($slugger->slug($category->getName())));
            $em->persist($category);
            $em->flush();

            $this->addFlash('success', sprintf('Catégorie "%s" créée.', $category->getName()));
            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/category/form.html.twig', [
            'form'     => $form,
            'category' => $category,
            'isNew'    => true,
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(
        Category $category,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
    ): Response {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->setSlug(strtolower($slugger->slug($category->getName())));
            $em->flush();

            $this->addFlash('success', sprintf('Catégorie "%s" mise à jour.', $category->getName()));
            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/category/form.html.twig', [
            'form'     => $form,
            'category' => $category,
            'isNew'    => false,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Category $category,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_category_' . $category->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_category_index');
        }

        $name = $category->getName();
        $em->remove($category);
        $em->flush();

        $this->addFlash('success', sprintf('Catégorie "%s" supprimée.', $name));
        return $this->redirectToRoute('admin_category_index');
    }
}
