<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Retourne un QueryBuilder pour la recherche de produits par terme.
     * Recherche dans le nom et la description (LIKE %term%).
     * Utile pour la pagination (retourne le QueryBuilder, pas le résultat exécuté).
     *
     * @param string $term
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function searchByTerm(string $term)
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.name LIKE :term OR p.description LIKE :term')
            ->setParameter('term', '%'.trim($term).'%')
            ->orderBy('p.name', 'ASC');

        return $qb;
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
