<?php
namespace App\Service;

use App\Repository\MotsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;

class GameService
{
    private MotsRepository $motsRepository;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(MotsRepository $motsRepository, EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->motsRepository = $motsRepository;
        $this->em = $em;
        $this->logger = $logger;  // LoggerInterface ajouté ici
    }

    public function getRandomWord(int $length, int $difficulty): array
    {
        $word = $this->motsRepository->findRandomByLengthAndDifficulty($length, $difficulty);

        if (!$word) {
            throw new HttpException(Response::HTTP_NOT_FOUND, 'Mot non trouvé.');
        }

        return ['word' => $word->getMot()];
    }

    public function checkGuess(string $guess, string $target, int $attempt, int $difficulty): array
    {
        $result = [];
        $usedLetters = [];
        $length = mb_strlen($guess, 'UTF-8');

        // 1ère passe : lettres bien placées
        for ($i = 0; $i < $length; $i++) {
            $guessChar = mb_substr($guess, $i, 1, 'UTF-8');
            $targetChar = mb_substr($target, $i, 1, 'UTF-8');
            if ($guessChar === $targetChar) {
                $result[$i] = ['letter' => $guessChar, 'status' => 'correct'];
                $usedLetters[$i] = true;
            } else {
                $result[$i] = ['letter' => $guessChar, 'status' => 'absent'];
            }
        }

        // 2ème passe : lettres mal placées
        for ($i = 0; $i < $length; $i++) {
            if ($result[$i]['status'] === 'absent') {
                for ($j = 0; $j < $length; $j++) {
                    if ((!isset($usedLetters[$j]) || !$usedLetters[$j]) &&
                        mb_substr($guess, $i, 1, 'UTF-8') === mb_substr($target, $j, 1, 'UTF-8')) {
                        $result[$i]['status'] = 'misplaced';
                        $usedLetters[$j] = true;
                        break;
                    }
                }
            }
        }

        // Log des paramètres du calcul de score
        $this->logger->info('Calcul du score:', [
            'word' => $target,
            'attempt' => $attempt,
            'difficulty' => $difficulty,
            'calculated_points' => $this->calculatePoints($target, $attempt, $difficulty)
        ]);

        $score = $this->calculatePoints($target, $attempt, $difficulty);

        return ['feedback' => $result, 'score' => $score];
    }

    private function calculatePoints(string $word, int $attempt, int $difficulty): int
    {
        // Log des paramètres avant le calcul
        $length = mb_strlen($word, 'UTF-8');
        $attemptFactor = max(1, 7 - $attempt);

        return $length * $difficulty * $attemptFactor;
    }
}


