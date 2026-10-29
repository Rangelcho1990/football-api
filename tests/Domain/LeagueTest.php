<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\League\League;
use PHPUnit\Framework\TestCase;

final class LeagueTest extends TestCase
{
    public function testRetainsAssignedValuesAndSupportsFluentSetters(): void
    {
        $entity = new League();
        $value = 42;
        self::assertSame($entity, $entity->setId($value));
        self::assertSame($value, $entity->getId());
        $value = 'Premier League';
        self::assertSame($entity, $entity->setName($value));
        self::assertSame($value, $entity->getName());
        $value = 'premier-league';
        self::assertSame($entity, $entity->setLeagueNameSlugged($value));
        self::assertSame($value, $entity->getLeagueNameSlugged());
        $value = 'league.png';
        self::assertSame($entity, $entity->setLogo($value));
        self::assertSame($value, $entity->getLogo());
        $value = 'League';
        self::assertSame($entity, $entity->setLeagueType($value));
        self::assertSame($value, $entity->getLeagueType());
        $value = 39;
        self::assertSame($entity, $entity->setLeagueApiId($value));
        self::assertSame($value, $entity->getLeagueApiId());
        $value = 48;
        self::assertSame($entity, $entity->setCountryId($value));
        self::assertSame($value, $entity->getCountryId());
        $value = 1;
        self::assertSame($entity, $entity->setStatus($value));
        self::assertSame($value, $entity->getStatus());
        $value = new \DateTime('2026-01-01');
        self::assertSame($entity, $entity->setLastUpdated($value));
        self::assertSame($value, $entity->getLastUpdated());
    }
}
