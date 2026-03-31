<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findVisibleByCategory(int $categoryId, ?int $subCategoryId = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.category = :categoryId')
            ->andWhere('p.isVisible = true')
            ->setParameter('categoryId', $categoryId)
            ->orderBy('p.displayOrder', 'ASC');

        if ($subCategoryId !== null) {
            $qb->andWhere('p.subCategory = :subCategoryId')
               ->setParameter('subCategoryId', $subCategoryId);
        }

        return $qb->getQuery()->getResult();
    }

    public function findFeatured(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.isFeatured = true')
            ->andWhere('p.isVisible = true')
            ->orderBy('p.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findTopViewed(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.viewCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
