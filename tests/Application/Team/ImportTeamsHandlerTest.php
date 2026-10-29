<?php

declare(strict_types=1);

namespace App\Tests\Application\Team;

use App\Application\Team\DTO\SourceTeamDTO;
use App\Application\Team\ImportTeamsHandler;
use App\Domain\Team\Team;
use App\Domain\Team\TeamRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class ImportTeamsHandlerTest extends TestCase
{
    public function testSkipsExistingTeams(): void
    {
        $repository = $this->createMock(TeamRepositoryInterface::class);
        $repository->expects(self::once())->method('findByApiId')->with(33)->willReturn(new Team());
        $repository->expects(self::never())->method('createTeam');

        (new ImportTeamsHandler($repository))->handler([new SourceTeamDTO('Manchester United', null, 33, 48, null, null, true, new \DateTime())]);
    }

    public function testImportsNullableFields(): void
    {
        $repository = $this->createMock(TeamRepositoryInterface::class);
        $repository->expects(self::once())->method('findByApiId')->with(33)->willReturn(null);
        $repository->expects(self::once())->method('createTeam')->with(self::callback(
            static function (Team $team): bool {
                self::assertNull($team->getLogo());
                self::assertNull($team->getFounded());
                self::assertNull($team->getNational());
                self::assertNull($team->getStatus());
                self::assertNull($team->getLastUpdated());

                return true;
            },
        ));

        (new ImportTeamsHandler($repository))->handler([new SourceTeamDTO('Manchester United', null, 33, 48, null, null, null, null)]);
    }

    public function testRepeatedTeamsAreOnlyCreatedOnce(): void
    {
        $dto = new SourceTeamDTO('Manchester United', 'https://example.com/33.png', 33, 48, 1878, false, true, new \DateTime());
        $created = null;
        $repository = $this->createMock(TeamRepositoryInterface::class);
        $repository->expects(self::exactly(2))->method('findByApiId')->with(33)
            ->willReturnCallback(static function () use (&$created): ?Team {
                return $created;
            });
        $repository->expects(self::once())->method('createTeam')->willReturnCallback(
            static function (Team $team) use (&$created, $dto): void {
                self::assertSame($dto->teamName, $team->getName());
                self::assertSame($dto->teamLogo, $team->getLogo());
                self::assertSame($dto->teamApiId, $team->getTeamApiId());
                self::assertSame($dto->teamCountryId, $team->getCountryId());
                self::assertSame($dto->teamFounded, $team->getFounded());
                self::assertSame($dto->teamNational, $team->getNational());
                self::assertSame($dto->teamStatus, $team->getStatus());
                self::assertSame($dto->teamLastUpdated, $team->getLastUpdated());
                $created = $team;
            },
        );

        (new ImportTeamsHandler($repository))->handler([$dto, $dto]);
    }
}
