<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Model\Entity\Review;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Tests\Functional\FunctionalTestCase;

final class ReviewTest extends FunctionalTestCase
{
    public function testLoggedUserCanAddReviewWithComment(): void
    {
        // On connecte un utilisateur des fixtures.
        $this->login('user+9@email.com');

        // On récupère cet utilisateur en base.
        $user = $this->getEntityManager()
            ->getRepository(User::class)
            ->findOneBy(['email' => 'user+9@email.com']);

        // On cherche un jeu que cet utilisateur n'a pas encore noté.
        $videoGame = $this->findVideoGameNotReviewedByUser($user);

        // On ouvre la page du jeu trouvé.
        $this->get('/'.$videoGame->getSlug());

        // On vérifie que la page s'affiche bien.
        self::assertResponseIsSuccessful();

        // Sécurité : on vérifie que le formulaire est bien visible.
        self::assertSelectorExists('button[type="submit"]');

        // On soumet le formulaire d'avis.
        $this->client->submitForm('Poster', [
            'review[rating]' => 5,
            'review[comment]' => 'Excellent jeu, très bonne expérience.',
        ]);

        // Après l'envoi, le contrôleur redirige vers la fiche du jeu.
        self::assertResponseRedirects('/'.$videoGame->getSlug());

        // On suit la redirection.
        $this->client->followRedirect();

        // On vérifie que le commentaire apparaît bien dans le HTML.
        self::assertSelectorTextContains('body', 'Excellent jeu, très bonne expérience.');

        // On vérifie aussi que l'avis est bien enregistré en base.
        $review = $this->getEntityManager()
            ->getRepository(Review::class)
            ->findOneBy([
                'user' => $user,
                'videoGame' => $videoGame,
                'comment' => 'Excellent jeu, très bonne expérience.',
            ]);

        self::assertNotNull($review);
        self::assertSame(5, $review->getRating());
    }

    public function testLoggedUserCanAddReviewWithoutComment(): void
    {
        // On connecte un autre utilisateur.
        $this->login('user+8@email.com');

        // On récupère l'utilisateur en base.
        $user = $this->getEntityManager()
            ->getRepository(User::class)
            ->findOneBy(['email' => 'user+8@email.com']);

        // On cherche un jeu que cet utilisateur n'a pas encore noté.
        $videoGame = $this->findVideoGameNotReviewedByUser($user);

        // On ouvre la page du jeu.
        $this->get('/'.$videoGame->getSlug());

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('button[type="submit"]');

        // On envoie une note sans commentaire.
        $this->client->submitForm('Poster', [
            'review[rating]' => 4,
            'review[comment]' => '',
        ]);

        self::assertResponseRedirects('/'.$videoGame->getSlug());

        // On vérifie que l'avis existe en base.
        $review = $this->getEntityManager()
            ->getRepository(Review::class)
            ->findOneBy([
                'user' => $user,
                'videoGame' => $videoGame,
                'rating' => 4,
            ]);

        self::assertNotNull($review);
        self::assertSame(4, $review->getRating());

        // Le commentaire est optionnel, donc il peut être null ou vide selon Symfony/Doctrine.
        self::assertTrue($review->getComment() === null || $review->getComment() === '');
    }

    private function findVideoGameNotReviewedByUser(User $user): VideoGame
    {
        $videoGames = $this->getEntityManager()
            ->getRepository(VideoGame::class)
            ->findAll();

        foreach ($videoGames as $videoGame) {
            if (!$videoGame->hasAlreadyReview($user)) {
                return $videoGame;
            }
        }

        self::fail('Aucun jeu disponible pour ajouter un avis avec cet utilisateur.');
    }
}