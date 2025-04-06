<?php

namespace App\Service;

use App\Entity\Scores;
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

    public function getScores(): array
    {
        try {
            return $this->repository->findAll();
        } catch (\Throwable $e) {
            // On peut ajouter du log ici si nécessaire.
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Erreur lors de la récupération des scores');
        }
    }

    public function updateScore($user, int $score): void
    {
        if ($user) {
            $scoreRepo = $this->entityManager->getRepository(Scores::class);
            $scoreEntity = $scoreRepo->findOneBy(['user' => $user]);

            if (!$scoreEntity) {
                $scoreEntity = new Scores();
                $scoreEntity->setUser($user);
                $scoreEntity->setScore($score);
                $this->entityManager->persist($scoreEntity);
            } else {
                $scoreEntity->setScore($scoreEntity->getScore() + $score);
            }

            $this->entityManager->flush();
        }
    }
}
