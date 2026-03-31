<?php

namespace App\Twig;

use App\Repository\CategoryRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('all_categories', $this->getAllCategories(...)),
        ];
    }

    /**
     * Retourne toutes les catégories visibles pour la navbar.
     * Mise en cache côté Twig (appelé N fois → une seule requête SQL grâce au cache de Doctrine).
     *
     * @return \App\Entity\Category[]
     */
    public function getAllCategories(): array
    {
        return $this->categoryRepository->findAllVisible();
    }
}
