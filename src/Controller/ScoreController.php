<?php

namespace App\Controller;

use App\Service\ScoreService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class ScoreController extends AbstractController
{
    #[Route('/api/scores', name: 'app_scores', methods: ['GET'])]
    public function getScores(ScoreService $scoreService): JsonResponse
    {
        try {
            $scores = $scoreService->getScores();
            return new JsonResponse([
                'message' => 'Scores récupérés avec succès',
                'data' => $scores
            ], Response::HTTP_OK);
        } catch (HttpExceptionInterface $e) {
            return new JsonResponse([
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getStatusCode()
                ]
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => [
                    'message' => 'Erreur lors de la récupération des scores',
                    'code' => Response::HTTP_INTERNAL_SERVER_ERROR
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
