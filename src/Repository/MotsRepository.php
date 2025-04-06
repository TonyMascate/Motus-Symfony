<?php

namespace App\Repository;

use App\Entity\Mots;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mots>
 */
class MotsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mots::class);
    }

//    /**
//     * @return Mots[] Returns an array of Mots objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Mots
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
    public function findRandomByLengthAndDifficulty(int $length, int $difficulty): ?Mots
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.longueur = :length')
            ->andWhere('m.difficulte = :difficulty')
            ->setParameter('length', $length)
            ->setParameter('difficulty', $difficulty);

        $results = $qb->getQuery()->getResult();

        if (empty($results)) {
            return null;
        }

        // Tirage aléatoire côté PHP
        return $results[array_rand($results)];
    }

}
