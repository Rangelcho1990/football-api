<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\Game\Game;
use PHPUnit\Framework\TestCase;

final class GameTest extends TestCase
{
    public function testRetainsAssignedValuesAndSupportsFluentSetters(): void
    {
        $entity = new Game();
        $value = 7;
        self::assertSame($entity, $entity->setId($value));
        self::assertSame($value, $entity->getId());
        $value = '2-1';
        self::assertSame($entity, $entity->setScore($value));
        self::assertSame($value, $entity->getScore());
        $value = new \App\Domain\Team\Team();
        self::assertSame($entity, $entity->setHomeTeam($value));
        self::assertSame($value, $entity->getHomeTeam());
        $value = new \App\Domain\Team\Team();
        self::assertSame($entity, $entity->setAwayTeam($value));
        self::assertSame($value, $entity->getAwayTeam());
        $value = new \DateTimeImmutable('2026-01-01');
        self::assertSame($entity, $entity->setGameTime($value));
        self::assertSame($value, $entity->getGameTime());
        $value = new \App\Domain\League\League();
        self::assertSame($entity, $entity->setLeague($value));
        self::assertSame($value, $entity->getLeague());
    }

    public function testScoreStartsUnsetAndCanBeCleared(): void
    {
        $game = new Game();
        self::assertNull($game->getScore());
        $game->setScore('0-0');
        self::assertNull($game->setScore(null)->getScore());
    }
}
