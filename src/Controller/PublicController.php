<?php

namespace App\Controller;

use App\Repository\AboutContentRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PublicController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
    ): Response {
        return $this->render('public/home.html.twig', [
            'categories' => $categoryRepository->findAllVisible(),
            'featured'   => $productRepository->findFeatured(),
        ]);
    }

    #[Route('/categorie/{slug}', name: 'app_category')]
    public function category(
        string $slug,
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
    ): Response {
        $category = $categoryRepository->findOneBy(['slug' => $slug, 'isVisible' => true]);
        if ($category === null) {
            throw $this->createNotFoundException('Catégorie introuvable.');
        }

        // Récupération de l'éventuel filtre sous-catégorie passé en query string
        $subCategoryId = null;
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $subParam = $request?->query->getInt('sous-categorie');
        if ($subParam > 0) {
            $subCategoryId = $subParam;
        }

        $products = $productRepository->findVisibleByCategory($category->getId(), $subCategoryId);

        return $this->render('public/category.html.twig', [
            'category'      => $category,
            'subCategories' => $category->getSubCategories()->filter(fn($s) => $s->isVisible())->toArray(),
            'products'      => $products,
            'activeSubId'   => $subCategoryId,
        ]);
    }

    #[Route('/a-propos', name: 'app_about')]
    public function about(AboutContentRepository $aboutRepository): Response
    {
        $about = $aboutRepository->getSingleton();

        return $this->render('public/about.html.twig', [
            'about' => $about,
        ]);
    }

    #[Route('/mentions-legales', name: 'app_legal')]
    public function legal(): Response
    {
        return $this->render('public/legal.html.twig');
    }
}
