<?php

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\Review;
use App\Model\Entity\Tag;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\CalculateAverageRating;
use App\Rating\CountRatingsPerValue;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

use function array_fill_callback;

final class VideoGameFixtures extends Fixture implements DependentFixtureInterface
{
    // 🔹 On injecte Faker pour générer des données réalistes
    // 🔹 On injecte aussi les services métier pour recalculer les notes
    public function __construct(
        private readonly Generator $faker,
        private readonly CalculateAverageRating $calculateAverageRating,
        private readonly CountRatingsPerValue $countRatingsPerValue
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // On récupère tous les utilisateurs créés dans UserFixtures
        $users = $manager->getRepository(User::class)->findAll();

        // =========================================================
        // CRÉATION DES JEUX VIDÉO
        // =========================================================

        // On crée 50 jeux vidéo avec Faker
        $videoGames = array_fill_callback(0, 50, fn (int $index): VideoGame => (new VideoGame)
            ->setTitle(sprintf('Jeu vidéo %d', $index)) // titre simple
            ->setDescription($this->faker->paragraphs(10, true)) // description réaliste
            ->setReleaseDate(new DateTimeImmutable())
            ->setTest($this->faker->paragraphs(6, true)) // contenu "test"
            ->setRating(($index % 5) + 1) // note interne (1 à 5)
            ->setImageName(sprintf('video_game_%d.png', $index))
            ->setImageSize(2_098_872)
        );

        // =========================================================
        // CRÉATION DES TAGS
        // =========================================================

        // Liste de tags réalistes (important pour le filtre)
        $tagNames = [
            'Action',
            'Aventure',
            'RPG',
            'Stratégie',
            'Simulation',
            'Multijoueur',
            'Indépendant',
            'Science-fiction',
            'Fantasy',
            'Horreur',
            'Coopération',
            'Open World',
        ];

        $tags = [];

        // On crée les entités Tag
        foreach ($tagNames as $tagName) {
            $tag = (new Tag())->setName($tagName);
            $tags[] = $tag;

            // IMPORTANT : persist pour sauvegarder en base
            $manager->persist($tag);
        }

        // =========================================================
        // ASSOCIATION TAGS ↔ JEUX VIDÉO
        // =========================================================

        foreach ($videoGames as $index => $videoGame) {

            //Chaque jeu aura entre 1 et 3 tags
            $numberOfTags = ($index % 3) + 1;

            //On choisit des tags aléatoires
            $tagIndexes = array_rand($tags, $numberOfTags);

            // array_rand peut retourner un int si 1 seul élément
            if (!is_array($tagIndexes)) {
                $tagIndexes = [$tagIndexes];
            }

            // On ajoute les tags au jeu
            foreach ($tagIndexes as $tagIndex) {
                $videoGame->getTags()->add($tags[$tagIndex]);
            }

            $manager->persist($videoGame);
        }

        // On sauvegarde jeux + tags
        $manager->flush();

        // =========================================================
        // CRÉATION DES REVIEWS (notes + commentaires)
        // =========================================================

        // Liste de commentaires réalistes (certains NULL = optionnel)
        $sampleComments = [
            'Très bon jeu, prise en main rapide.',
            'Univers intéressant et gameplay solide.',
            'Bonne surprise, même si quelques défauts techniques sont présents.',
            'Jeu agréable à parcourir, surtout en coopération.',
            'Expérience correcte mais un peu répétitive.',
            null,
            null,
        ];

        foreach ($videoGames as $index => $videoGame) {

            //Maximum 5 reviews par jeu
            $maxReviews = min(5, count($users));

            // Certains jeux auront 0 review (important pour tester)
            $numberOfReviews = $index % ($maxReviews + 1);

            if ($numberOfReviews === 0) {
                continue;
            }

            // On mélange les utilisateurs pour éviter les doublons
            $availableUsers = $users;
            shuffle($availableUsers);

            // On sélectionne des utilisateurs différents
            $selectedUsers = array_slice($availableUsers, 0, $numberOfReviews);

            foreach ($selectedUsers as $reviewIndex => $user) {

                $review = (new Review())
                    ->setVideoGame($videoGame)
                    ->setUser($user)

                    //Note entre 1 et 5
                    ->setRating((($index + $reviewIndex) % 5) + 1)

                    // Commentaire parfois NULL (champ optionnel)
                    ->setComment($sampleComments[array_rand($sampleComments)]);

                $manager->persist($review);
            }
        }

        // On sauvegarde les reviews
        $manager->flush();

        // =========================================================
        // RECALCUL DES DONNÉES (IMPORTANT)
        // =========================================================

        foreach ($videoGames as $videoGame) {

            // Recharge les données depuis la base
            // (important pour récupérer les reviews)
            $manager->refresh($videoGame);

            //Calcul de la moyenne des notes
            $this->calculateAverageRating->calculateAverage($videoGame);

            //Calcul du nombre de notes par valeur (1,2,3,4,5)
            $this->countRatingsPerValue->countRatingsPerValue($videoGame);

            $manager->persist($videoGame);
        }

        $manager->flush();
    }

    //Cette fixture dépend de UserFixtures
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}