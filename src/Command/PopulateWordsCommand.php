<?php

namespace App\Command;

use App\Entity\Mots;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'app:populate-words',
    description: 'Add a short description for your command',
)]
class PopulateWordsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private $httpClient;
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->httpClient = HttpClient::create();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    /**
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Truncate la table "Mots" avant d'ajouter les nouveaux mots
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE mots');
        // Boucle sur les longueurs de mots (6 à 10 lettres)
        for ($length = 6; $length <= 10; $length++) {
            $url = "https://trouve-mot.fr/api/size/" . $length . "/500";

            $output->writeln("Récupération des mots de $length lettres...");

            try {
                // Appel à l'API pour récupérer les mots
                $response = $this->httpClient->request('GET', $url);

                // Vérification de la réponse brute
                $output->writeln('Réponse brute : ' . $response->getContent());

                // Décodage de la réponse JSON
                try {
                    $data = $response->toArray(); // Transformer la réponse en tableau
                } catch (DecodingExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
                    $output->writeln('Erreur lors du décodage de la réponse JSON : ' . $e->getMessage());
                    return Command::FAILURE;
                }

                // Vérifier si des mots ont été retournés
                if (empty($data)) {
                    $output->writeln("Aucun mot trouvé pour $length lettres.");
                    continue;
                }

                // Traitement des mots récupérés
                foreach ($data as $wordData) {
                    // Récupération du mot et de la fréquence
                    $word = $wordData['name'];

                    // Calcul de la difficulté
                    $difficulty = $this->determineDifficulty($word);

                    // Création de l'entité Mots
                    $mot = new Mots();
                    $mot->setMot($word);
                    $mot->setLongueur($length);
                    $mot->setDifficulte((int) $difficulty);

                    // Sauvegarde de l'entité dans la base de données
                    $this->entityManager->persist($mot);
                }

                // Enregistrement en base de données après avoir traité tous les mots
                $this->entityManager->flush();
                $output->writeln("Mots de $length lettres ajoutés à la base de données.");
            } catch (TransportExceptionInterface $e) {
                // Gestion des erreurs liées à l'API
                $output->writeln('Erreur de connexion à l\'API Datamuse : ' . $e->getMessage());
                return Command::FAILURE;
            } catch (\Exception $e) {
                // Gestion de toutes les autres exceptions
                $output->writeln('Une erreur inattendue est survenue pour $length lettres : ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $output->writeln('Base de données remplie avec succès!');
        return Command::SUCCESS;
    }

    private function determineDifficulty(string $word): int
    {
        $length = strlen($word);
        $lowerWord = strtolower($word);

        // Lettres rares
        $rareLetters = ['k', 'w', 'z', 'x', 'q', 'y'];
        $rareLetterCount = 0;
        foreach (str_split($lowerWord) as $char) {
            if (in_array($char, $rareLetters)) {
                $rareLetterCount++;
            }
        }

        // Digrammes complexes
        $complexPatterns = ['gn', 'ph', 'th', 'ch', 'ou', 'oi', 'ui'];
        $complexityScore = 0;
        foreach ($complexPatterns as $pattern) {
            if (str_contains($lowerWord, $pattern)) {
                $complexityScore++;
            }
        }

        // Syllabes
        preg_match_all('/[aeiouy]+/i', $lowerWord, $matches);
        $syllableCount = count($matches[0]);

        // Score pondéré
        $score = ($length * 0.7) + ($rareLetterCount * 2) + ($complexityScore * 1.7) + ($syllableCount * 0.9);

        // Barèmes affinés
        if ($score <= 8) {
            return 1; // Facile
        } elseif ($score <= 10) {
            return 2; // Moyen
        } else {
            return 3; // Difficile
        }
    }



}
