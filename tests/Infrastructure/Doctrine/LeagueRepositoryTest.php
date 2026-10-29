<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Doctrine;

use App\Domain\League\Exception\LeagueAlreadyExistsException;
use App\Domain\League\League;
use App\Infrastructure\Doctrine\LeagueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class LeagueRepositoryTest extends TestCase
{
    public function testPersistsAndFlushesEntity(): void
    {
        $entity = new League();
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())->method('getClassMetadata')->with(League::class)->willReturn(new ClassMetadata(League::class));
        $manager->expects(self::once())->method('persist')->with($entity);
        $manager->expects(self::once())->method('flush');
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);

        (new LeagueRepository($registry))->createLeague($entity);
    }

    public function testPreservesCauseWhenPersistenceFails(): void
    {
        $entity = new League();
        $cause = new \RuntimeException('Database failure');

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getClassMetadata')->willReturn(new ClassMetadata(League::class));
        $manager->expects(self::once())->method('persist')->with($entity);
        $manager->expects(self::once())->method('flush')->willThrowException($cause);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);

        try {
            (new LeagueRepository($registry))->createLeague($entity);
            self::fail('Expected a domain exception.');
        } catch (LeagueAlreadyExistsException $exception) {
            self::assertSame($cause, $exception->getPrevious());
            self::assertSame('A league with these details already exists.', $exception->getMessage());
        }
    }

    public function testFindsByExternalApiId(): void
    {
        $entity = new League();
        $repository = $this->getMockBuilder(LeagueRepository::class)->disableOriginalConstructor()->onlyMethods(['findOneBy'])->getMock();
        $repository->expects(self::exactly(2))->method('findOneBy')->with(['leagueApiId' => 33])->willReturnOnConsecutiveCalls($entity, null);

        self::assertSame($entity, $repository->findByApiId(33));
        self::assertNull($repository->findByApiId(33));
    }
}
