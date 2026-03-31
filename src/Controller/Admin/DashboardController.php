<?php

namespace App\Controller\Admin;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'dashboard')]
    public function index(
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        UserRepository $userRepository,
    ): Response {
        $totalProducts  = count($productRepository->findAll());
        $totalVisible   = count($productRepository->findBy(['isVisible' => true]));
        $totalFeatured  = count($productRepository->findBy(['isFeatured' => true]));
        $topViewed      = $productRepository->findTopViewed(5);
        $totalCategories = count($categoryRepository->findAll());

        return $this->render('admin/dashboard.html.twig', [
            'totalProducts'   => $totalProducts,
            'totalVisible'    => $totalVisible,
            'totalFeatured'   => $totalFeatured,
            'topViewed'       => $topViewed,
            'totalCategories' => $totalCategories,
        ]);
    }
}
