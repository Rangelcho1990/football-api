<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\DataFixtures\AppFixtures;
use App\Domain\League\League;
use App\Domain\User\User;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixturesTest extends TestCase
{
    public function testLoadsDemoLeagueAndUserWithHashedPassword(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::once())->method('hashPassword')->with(self::isInstanceOf(User::class), 'demo-password')->willReturn('demo-hash');
        $persisted = [];
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::exactly(2))->method('persist')->willReturnCallback(static function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });
        $manager->expects(self::once())->method('flush')->willReturnCallback(static function () use (&$persisted): void {
            self::assertCount(2, $persisted);
        });

        (new AppFixtures($hasher))->load($manager);

        [$league, $user] = $persisted;
        self::assertInstanceOf(League::class, $league);
        self::assertSame('Premier League', $league->getName());
        self::assertSame('premier-league', $league->getLeagueNameSlugged());
        self::assertSame(123, $league->getLeagueApiId());
        self::assertSame('premier-league-logo.png', $league->getLogo());
        self::assertInstanceOf(User::class, $user);
        self::assertSame('demo', $user->getUsername());
        self::assertSame('demo@example.com', $user->getEmail());
        self::assertTrue($user->isActive());
        self::assertSame('demo-hash', $user->getPassword());
    }
}
