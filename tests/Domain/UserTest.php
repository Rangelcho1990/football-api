<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testRetainsAssignedValuesAndSupportsFluentSetters(): void
    {
        $entity = new User();
        $value = 9;
        self::assertSame($entity, $entity->setId($value));
        self::assertSame($value, $entity->getId());
        $value = 'demo';
        self::assertSame($entity, $entity->setUsername($value));
        self::assertSame($value, $entity->getUsername());
        $value = 'hashed-password';
        self::assertSame($entity, $entity->setPassword($value));
        self::assertSame($value, $entity->getPassword());
        $value = 'demo@example.com';
        self::assertSame($entity, $entity->setEmail($value));
        self::assertSame($value, $entity->getEmail());
        $value = new \DateTimeImmutable('2026-01-01');
        self::assertSame($entity, $entity->setCreatedAt($value));
        self::assertSame($value, $entity->getCreatedAt());
        $value = 120;
        self::assertSame($entity, $entity->setPoint($value));
        self::assertSame($value, $entity->getPoint());
        $value = 4;
        self::assertSame($entity, $entity->setAvatar($value));
        self::assertSame($value, $entity->getAvatar());
        $value = true;
        self::assertSame($entity, $entity->setActive($value));
        self::assertSame($value, $entity->isActive());
        self::assertSame('demo', $entity->getUserIdentifier());
        self::assertSame(['PUBLIC_ACCESS'], $entity->getRoles());
        $entity->eraseCredentials();
        self::assertSame('hashed-password', $entity->getPassword());
    }

    public function testNewUserDefaults(): void
    {
        $before = new \DateTimeImmutable();
        $user = new User();
        self::assertSame(1, $user->getAvatar());
        self::assertSame(0, $user->getPoint());
        self::assertFalse($user->isActive());
        self::assertGreaterThanOrEqual($before, $user->getCreatedAt());
        self::assertLessThanOrEqual(new \DateTimeImmutable(), $user->getCreatedAt());
    }

    public function testRejectsEmptyUsername(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Username must not be empty.');
        (new User())->setUsername('');
    }
}
