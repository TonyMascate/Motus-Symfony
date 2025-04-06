<?php

namespace App\Controller;

use App\Exception\ErrorResponseFactory;
use App\Service\ScoreService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class ScoreController extends AbstractController
{
    #[Route('/api/scores', name: 'app_scores', methods: ['GET'])]
    public function getScores(ScoreService $scoreService, SerializerInterface $serializer): JsonResponse
    {
        try {
            $payload = $serializer->serialize([
                'message' => 'Scores récupérés avec succès',
                'data' => $scoreService->getScores()
            ], 'json', ['groups' => 'score:read']);

            return new JsonResponse($payload, Response::HTTP_OK, [], true);
        } catch (HttpExceptionInterface $e) {
            return ErrorResponseFactory::create($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ErrorResponseFactory::create(
                'Erreur lors de la récupération des scores',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
