<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\Team\Team;
use PHPUnit\Framework\TestCase;

final class TeamTest extends TestCase
{
    public function testRetainsAssignedValuesAndSupportsFluentSetters(): void
    {
        $entity = new Team();
        $value = 33;
        self::assertSame($entity, $entity->setId($value));
        self::assertSame($value, $entity->getId());
        $value = 'United';
        self::assertSame($entity, $entity->setName($value));
        self::assertSame($value, $entity->getName());
        $value = 'team.png';
        self::assertSame($entity, $entity->setLogo($value));
        self::assertSame($value, $entity->getLogo());
        $value = 33;
        self::assertSame($entity, $entity->setTeamApiId($value));
        self::assertSame($value, $entity->getTeamApiId());
        $value = 48;
        self::assertSame($entity, $entity->setCountryId($value));
        self::assertSame($value, $entity->getCountryId());
        $value = 1878;
        self::assertSame($entity, $entity->setFounded($value));
        self::assertSame($value, $entity->getFounded());
        $value = false;
        self::assertSame($entity, $entity->setNational($value));
        self::assertSame($value, $entity->getNational());
        $value = false;
        self::assertSame($entity, $entity->setStatus($value));
        self::assertSame($value, $entity->getStatus());
        $value = new \DateTime('2026-01-01');
        self::assertSame($entity, $entity->setLastUpdated($value));
        self::assertSame($value, $entity->getLastUpdated());
    }

    public function testNullableFieldsCanBeCleared(): void
    {
        $team = new Team();
        self::assertNull($team->getLogo());
        self::assertNull($team->getFounded());
        self::assertNull($team->getNational());
        self::assertTrue($team->getStatus());
        self::assertNull($team->getLastUpdated());
        $team->setLogo('logo.png')->setFounded(1900)->setNational(true)->setStatus(false)->setLastUpdated(new \DateTime());
        self::assertNull($team->setLogo(null)->getLogo());
        self::assertNull($team->setFounded(null)->getFounded());
        self::assertNull($team->setNational(null)->getNational());
        self::assertNull($team->setStatus(null)->getStatus());
        self::assertNull($team->setLastUpdated(null)->getLastUpdated());
    }
}
