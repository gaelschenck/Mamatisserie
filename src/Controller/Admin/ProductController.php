<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/produits', name: 'admin_product_')]
#[IsGranted('ROLE_ADMIN')]
class ProductController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        PaginatorInterface $paginator,
    ): Response {
        $categoryId   = $request->query->getInt('categorie', 0) ?: null;
        $searchQuery  = $request->query->getString('q', '');

        $qb = $productRepository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.subCategory', 's')
            ->addSelect('c', 's')
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('p.displayOrder', 'ASC');

        if ($categoryId) {
            $qb->andWhere('c.id = :cat')->setParameter('cat', $categoryId);
        }
        if ($searchQuery !== '') {
            $qb->andWhere('p.name LIKE :q')->setParameter('q', '%' . $searchQuery . '%');
        }

        $pagination = $paginator->paginate($qb, $request->query->getInt('page', 1), 20);

        return $this->render('admin/product/index.html.twig', [
            'pagination'  => $pagination,
            'categories'  => $categoryRepository->findAllOrdered(),
            'categoryId'  => $categoryId,
            'searchQuery' => $searchQuery,
        ]);
    }

    #[Route('/nouveau', name: 'new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $product = new Product();
        $form    = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', sprintf('Produit "%s" créé.', $product->getName()));
            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/product/form.html.twig', [
            'form'    => $form,
            'product' => $product,
            'isNew'   => true,
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(
        Product $product,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', sprintf('Produit "%s" mis à jour.', $product->getName()));
            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/product/form.html.twig', [
            'form'    => $form,
            'product' => $product,
            'isNew'   => false,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Product $product,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('delete_product_' . $product->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_product_index');
        }

        $name = $product->getName();
        $em->remove($product);
        $em->flush();

        $this->addFlash('success', sprintf('Produit "%s" supprimé.', $name));
        return $this->redirectToRoute('admin_product_index');
    }

    /**
     * Bascule rapide visibilité ou coup de cœur depuis la liste (AJAX-compatible).
     */
    #[Route('/{id}/toggle/{field}', name: 'toggle', requirements: ['id' => '\d+', 'field' => 'visible|featured'], methods: ['POST'])]
    public function toggle(
        Product $product,
        string $field,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('toggle_product_' . $product->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('admin_product_index');
        }

        if ($field === 'visible') {
            $product->setIsVisible(!$product->isVisible());
        } else {
            $product->setIsFeatured(!$product->isFeatured());
        }
        $em->flush();

        return $this->redirectToRoute('admin_product_index', $request->query->all());
    }
}
