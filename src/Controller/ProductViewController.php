<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ProductViewController extends AbstractController
{
    /**
     * Incrémente le compteur de vues d'un produit.
     * Appelé en JS à l'ouverture de la modale, sans recharger la page.
     */
    #[Route('/produit/{id}/vue', name: 'app_product_view', methods: ['POST'])]
    public function incrementView(
        int $id,
        ProductRepository $productRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('product_view_' . $id, (string) $this->container->get('request_stack')->getCurrentRequest()?->request->get('_csrf_token'))) {
            return new JsonResponse(['ok' => false], 400);
        }

        $product = $productRepository->find($id);
        if ($product === null || !$product->isVisible()) {
            return new JsonResponse(['ok' => false], 404);
        }

        $product->incrementViewCount();
        $em->flush();

        return new JsonResponse(['ok' => true, 'views' => $product->getViewCount()]);
    }
}
