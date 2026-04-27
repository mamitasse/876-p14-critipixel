<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Tests\Functional\FunctionalTestCase;

final class FilterTest extends FunctionalTestCase
{
    public function testShouldListTenVideoGames(): void
    {
        $this->get('/');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');

        $this->client->clickLink('2');

        self::assertResponseIsSuccessful();
    }

    public function testShouldFilterVideoGamesBySearch(): void
    {
        $this->get('/');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');

        $this->client->submitForm('Filtrer', [
            'filter[search]' => 'Jeu vidéo 49',
        ], 'GET');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'article.game-card');
    }

    public function testShouldFilterVideoGamesByTag(): void
    {
        $this->get('/');

        self::assertResponseIsSuccessful();

        $this->client->submitForm('Filtrer', [
            'filter[tags]' => [13],
        ], 'GET');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('article.game-card');
        self::assertSelectorTextContains('.video-games-list', 'Action');
    }
}