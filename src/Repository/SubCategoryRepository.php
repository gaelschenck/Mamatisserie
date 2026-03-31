<?php

namespace App\Repository;

use App\Entity\SubCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SubCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubCategory::class);
    }

    public function findByCategory(int $categoryId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.category = :categoryId')
            ->setParameter('categoryId', $categoryId)
            ->orderBy('s.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findVisibleByCategory(int $categoryId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.category = :categoryId')
            ->andWhere('s.isVisible = true')
            ->setParameter('categoryId', $categoryId)
            ->orderBy('s.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
