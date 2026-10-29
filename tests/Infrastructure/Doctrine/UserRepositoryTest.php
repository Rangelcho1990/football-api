<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Doctrine;

use App\Domain\User\Exception\UserAlreadyExistsException;
use App\Domain\User\User;
use App\Infrastructure\Doctrine\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class UserRepositoryTest extends TestCase
{
    public function testPersistsAndFlushesEntity(): void
    {
        $entity = new User();
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())->method('getClassMetadata')->with(User::class)->willReturn(new ClassMetadata(User::class));
        $manager->expects(self::once())->method('persist')->with($entity);
        $manager->expects(self::once())->method('flush');
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);

        (new UserRepository($registry))->createUser($entity);
    }

    public function testPreservesCauseWhenPersistenceFails(): void
    {
        $entity = new User();
        $cause = new \RuntimeException('Database failure');

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getClassMetadata')->willReturn(new ClassMetadata(User::class));
        $manager->expects(self::once())->method('persist')->with($entity);
        $manager->expects(self::once())->method('flush')->willThrowException($cause);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);

        try {
            (new UserRepository($registry))->createUser($entity);
            self::fail('Expected a domain exception.');
        } catch (UserAlreadyExistsException $exception) {
            self::assertSame($cause, $exception->getPrevious());
            self::assertSame('A user with these details already exists.', $exception->getMessage());
        }
    }
}
