<?php

namespace App\Controller;

use App\Exception\ErrorResponseFactory;
use App\Service\GameService;
use App\Service\ScoreService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class GameController extends AbstractController
{
    private GameService $gameService;
    private ScoreService $scoreService;

    public function __construct(GameService $gameService, ScoreService $scoreService)
    {
        $this->gameService = $gameService;
        $this->scoreService = $scoreService;
    }

    #[Route('/api/game', name: 'api_game', methods: ['GET'])]
    public function getWord(Request $request): JsonResponse
    {
        try {
            $length = $request->query->getInt('length', 6);
            $difficulty = $request->query->getInt('difficulty', 1);

            $wordData = $this->gameService->getRandomWord($length, $difficulty);

            return new JsonResponse([
                'word' => $wordData['word'],
                'length' => strlen($wordData['word']),
            ]);
        } catch (\Throwable $e) {
            return ErrorResponseFactory::create('Erreur lors de la récupération du mot', JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/game/guess', name: 'game_guess', methods: ['POST'])]
    public function guess(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $guess = $data['guess'] ?? '';
        $target = $data['target'] ?? '';
        $attempt = (int)($data['attempt'] ?? 6);
        $difficulty = (int)($data['difficulty'] ?? 1);

        if (!$guess || !$target) {
            return ErrorResponseFactory::create('Données manquantes', JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->gameService->checkGuess($guess, $target, $attempt, $difficulty);
            // Ajout du score si la réponse est bonne
            if($guess == $target){
                $this->scoreService->updateScore($this->getUser(), $result['score']);
            }

            return new JsonResponse(['result' => $result['feedback']]);
        } catch (\Throwable $e) {
            return ErrorResponseFactory::create('Erreur lors du traitement de la tentative', JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
