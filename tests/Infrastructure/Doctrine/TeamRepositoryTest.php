<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Doctrine;

use App\Domain\Team\Exception\TeamAlreadyExistsException;
use App\Domain\Team\Team;
use App\Infrastructure\Doctrine\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class TeamRepositoryTest extends TestCase
{
    public function testPersistsAndFlushesEntity(): void
    {
        $entity = new Team();
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())->method('getClassMetadata')->with(Team::class)->willReturn(new ClassMetadata(Team::class));
        $manager->expects(self::once())->method('persist')->with($entity);
        $manager->expects(self::once())->method('flush');
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);

        (new TeamRepository($registry))->createTeam($entity);
    }

    public function testPreservesCauseWhenPersistenceFails(): void
    {
        $entity = new Team();
        $driverException = $this->createStub(\Doctrine\DBAL\Driver\Exception::class);
        $driverException->method('getSQLState')->willReturn('23505');
        $cause = new \Doctrine\DBAL\Exception\UniqueConstraintViolationException($driverException, null);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getClassMetadata')->willReturn(new ClassMetadata(Team::class));
        $manager->expects(self::once())->method('persist')->with($entity);
        $manager->expects(self::once())->method('flush')->willThrowException($cause);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);

        try {
            (new TeamRepository($registry))->createTeam($entity);
            self::fail('Expected a domain exception.');
        } catch (TeamAlreadyExistsException $exception) {
            self::assertSame($cause, $exception->getPrevious());
            self::assertSame('A team with these details already exists.', $exception->getMessage());
        }
    }

    public function testFindsByExternalApiId(): void
    {
        $entity = new Team();
        $repository = $this->getMockBuilder(TeamRepository::class)->disableOriginalConstructor()->onlyMethods(['findOneBy'])->getMock();
        $repository->expects(self::exactly(2))->method('findOneBy')->with(['teamApiId' => 33])->willReturnOnConsecutiveCalls($entity, null);

        self::assertSame($entity, $repository->findByApiId(33));
        self::assertNull($repository->findByApiId(33));
    }

    public function testUnrelatedDatabaseErrorsAreNotReportedAsDuplicates(): void
    {
        $cause = new \RuntimeException('Connection lost');
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getClassMetadata')->willReturn(new ClassMetadata(Team::class));
        $manager->expects(self::once())->method('persist')->willThrowException($cause);
        $manager->expects(self::never())->method('flush');
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);

        $this->expectExceptionObject($cause);
        (new TeamRepository($registry))->createTeam(new Team());
    }
}
