<?php

declare(strict_types=1);

namespace App\Tests\Unit\Rating;

use App\Model\Entity\Review;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\RatingHandler;
use PHPUnit\Framework\TestCase;

/**
 * Test unitaire du calcul de la moyenne des notes.
 *
 * Ici, on teste uniquement la logique métier de RatingHandler :
 * - sans base de données
 * - sans navigateur
 * - sans formulaires
 *
 * On crée simplement un jeu vidéo, on lui ajoute des reviews,
 * puis on vérifie que la moyenne calculée correspond bien
 * au résultat attendu.
 */
final class CalculateAverageRatingTest extends TestCase
{
    /**
     * Ce test vérifie que la moyenne calculée pour un jeu vidéo
     * est correcte dans plusieurs scénarios.
     *
     * @dataProvider provideRatingsAndExpectedAverage
     */
    public function testShouldCalculateAverageRatingCorrectly(
        array $ratings,
        ?int $expectedAverage
    ): void {
        // =========================
        // 1. ARRANGE
        // =========================
        // On prépare les objets nécessaires au test.

        // Classe contenant l'algorithme que l'on veut tester
        $ratingHandler = new RatingHandler();

        // Jeu vidéo vide, sans review au départ
        $videoGame = new VideoGame();

        // On ajoute une review pour chaque note du scénario
        foreach ($ratings as $index => $rating) {
            $review = $this->createReview($rating, $index);
            $videoGame->getReviews()->add($review);
        }

        // =========================
        // 2. ACT
        // =========================
        // On exécute la méthode à tester.
        $ratingHandler->calculateAverage($videoGame);

        // =========================
        // 3. ASSERT
        // =========================
        // On vérifie que la moyenne obtenue est bien celle attendue.
        self::assertSame($expectedAverage, $videoGame->getAverageRating());
    }

    /**
     * Fournit plusieurs jeux de données au test.
     *
     * Chaque cas contient :
     * - un tableau de notes
     * - la moyenne attendue
     */
    public static function provideRatingsAndExpectedAverage(): iterable
    {
        // Aucun avis : la moyenne doit rester à null
        yield 'aucune note' => [
            [],
            null,
        ];

        // Une seule note : la moyenne correspond à cette note
        yield 'une seule note' => [
            [5],
            5,
        ];

        // Plusieurs notes identiques : moyenne entière
        yield 'moyenne entière' => [
            [4, 4, 4],
            4,
        ];

        // Cas important : la méthode utilise ceil()
        // (1 + 2) / 2 = 1.5 => ceil(1.5) = 2
        yield 'arrondi au supérieur' => [
            [1, 2],
            2,
        ];

        // Cas varié simple
        yield 'cas varié 1' => [
            [1, 3, 5],
            3,
        ];

        // Autre cas varié avec arrondi
        // (2 + 3 + 4 + 5) / 4 = 3.5 => ceil(3.5) = 4
        yield 'cas varié 2' => [
            [2, 3, 4, 5],
            4,
        ];
    }

    /**
     * Méthode utilitaire qui crée une review de test.
     *
     * On crée aussi un utilisateur fictif, car une Review
     * a besoin d'un User pour être cohérente.
     */
    private function createReview(int $rating, int $index): Review
    {
        $user = (new User())
            ->setUsername('user'.$index)
            ->setEmail('user'.$index.'@email.com')
            ->setPassword('password');

        return (new Review())
            ->setUser($user)
            ->setRating($rating);
    }
}