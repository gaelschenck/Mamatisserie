<?php

namespace App\Repository;

use App\Entity\AboutContent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AboutContentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AboutContent::class);
    }

    public function getSingleton(): ?AboutContent
    {
        return $this->findOneBy([]);
    }
}
