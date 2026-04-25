<?php

declare(strict_types=1);

namespace App\Tests\Unit\Rating;

use App\Model\Entity\Review;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\RatingHandler;


use PHPUnit\Framework\TestCase;

/**
 *  TEST UNITAIRE : Comptage des notes par valeur (1 à 5)
 *
 *  On vérifie combien de fois chaque note apparaît
 */
final class CountRatingsPerValueTest extends TestCase
{
    /**
     *  Test principal avec plusieurs scénarios
     */
   /**
 * @dataProvider provideRatingsAndExpectedCounts
 */
    public function testShouldCountRatingsPerValueCorrectly(
        array $ratings, // ex: [1,1,2,5]

        int $expectedOne,
        int $expectedTwo,
        int $expectedThree,
        int $expectedFour,
        int $expectedFive
    ): void {
        // =========================
        //  ARRANGE
        // =========================

        $ratingHandler = new RatingHandler();
        $videoGame = new VideoGame();

        // On ajoute des reviews
        foreach ($ratings as $index => $rating) {
            $review = $this->createReview($rating, $index);
            $videoGame->getReviews()->add($review);
        }

        // =========================
        // ACT
        // =========================

        $ratingHandler->countRatingsPerValue($videoGame);

        // =========================
        // ASSERT
        // =========================

        // On vérifie chaque compteur
        self::assertSame($expectedOne, $videoGame->getNumberOfRatingsPerValue()->getNumberOfOne());
        self::assertSame($expectedTwo, $videoGame->getNumberOfRatingsPerValue()->getNumberOfTwo());
        self::assertSame($expectedThree, $videoGame->getNumberOfRatingsPerValue()->getNumberOfThree());
        self::assertSame($expectedFour, $videoGame->getNumberOfRatingsPerValue()->getNumberOfFour());
        self::assertSame($expectedFive, $videoGame->getNumberOfRatingsPerValue()->getNumberOfFive());
    }

    /**
     * TEST IMPORTANT
     *
     * Vérifie que les compteurs sont remis à zéro
     * (car il y a un clear() dans le code)
     */
    public function testShouldResetCountsBeforeRecounting(): void
    {
        $ratingHandler = new RatingHandler();
        $videoGame = new VideoGame();

        //1er calcul : note = 5
        $videoGame->getReviews()->add($this->createReview(5, 1));
        $ratingHandler->countRatingsPerValue($videoGame);

        self::assertSame(1, $videoGame->getNumberOfRatingsPerValue()->getNumberOfFive());

        //On change complètement les reviews
        $videoGame->getReviews()->clear();
        $videoGame->getReviews()->add($this->createReview(1, 2));

        // On recalcule
        $ratingHandler->countRatingsPerValue($videoGame);

        // Les anciens résultats doivent disparaître
        self::assertSame(1, $videoGame->getNumberOfRatingsPerValue()->getNumberOfOne());
        self::assertSame(0, $videoGame->getNumberOfRatingsPerValue()->getNumberOfFive());
    }

    /**
     * DataProvider
     */
    public static function provideRatingsAndExpectedCounts(): iterable
    {
        yield 'aucune note' => [
            [],
            0, 0, 0, 0, 0,
        ];

        yield 'une note de chaque' => [
            [1, 2, 3, 4, 5],
            1, 1, 1, 1, 1,
        ];

        yield 'plusieurs notes identiques' => [
            [5, 5, 5],
            0, 0, 0, 0, 3,
        ];

        yield 'cas varié' => [
            [1, 1, 2, 4, 4, 4, 5],
            2, 1, 0, 3, 1,
        ];
    }

    /**
     * Création d'une review
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