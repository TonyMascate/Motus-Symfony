<?php

namespace App\Service;

use App\Repository\ScoresRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ScoreService
{
    private EntityManagerInterface $entityManager;
    private ScoresRepository $repository;

    public function __construct(EntityManagerInterface $entityManager, ScoresRepository $repository)
    {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
    }

    /**
     * Récupération des scores
     *
     * @return array
     * @throws HttpException en cas d'erreur lors de la récupération des scores.
     */
    public function getScores(): array
    {
        try {
            return $this->repository->findAll();
        } catch (\Throwable $e) {
            // On peut ajouter du log ici si nécessaire.
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Erreur lors de la récupération des scores');
        }
    }
}
