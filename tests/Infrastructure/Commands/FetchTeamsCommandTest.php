<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Commands;

use App\Application\Team\DTO\SourceTeamDTO;
use App\Application\Team\ImportTeamsHandler;
use App\Application\Team\TeamSourceInterface;
use App\Domain\Team\Team;
use App\Domain\Team\TeamRepositoryInterface;
use App\Infrastructure\Commands\FetchTeamsCommand;
use App\Infrastructure\Services\TeamSource\TeamSourceRegistry;
use Doctrine\DBAL\Exception as DatabaseException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class FetchTeamsCommandTest extends TestCase
{
    public function testImportsFromSelectedSource(): void
    {
        $source = $this->createMock(TeamSourceInterface::class);
        $source->expects(self::once())->method('fetchTeams')->willReturn([new SourceTeamDTO('United', 'logo.png', 33, 48, 1878, false, true, new \DateTime())]);
        $otherSource = $this->createMock(TeamSourceInterface::class);
        $otherSource->expects(self::never())->method('fetchTeams');
        $repository = $this->createMock(TeamRepositoryInterface::class);
        $repository->expects(self::once())->method('findByApiId')->with(33)->willReturn(null);
        $repository->expects(self::once())->method('createTeam')->with(self::isInstanceOf(Team::class));
        $registry = new TeamSourceRegistry(new \ArrayIterator(['rapid' => $otherSource, 'test' => $source]));
        $tester = new CommandTester(new FetchTeamsCommand($registry, new ImportTeamsHandler($repository)));

        self::assertSame(Command::SUCCESS, $tester->execute(['source' => 'test']));
        self::assertStringContainsString('Fetched 1 teams from "test".', $tester->getDisplay());
    }

    public function testUnknownSourceDoesNotImport(): void
    {
        $source = $this->createMock(TeamSourceInterface::class);
        $source->expects(self::never())->method('fetchTeams');
        $repository = $this->createMock(TeamRepositoryInterface::class);
        $repository->expects(self::never())->method('findByApiId');
        $repository->expects(self::never())->method('createTeam');
        $tester = new CommandTester(new FetchTeamsCommand(new TeamSourceRegistry(['rapid' => $source]), new ImportTeamsHandler($repository)));

        self::assertSame(Command::INVALID, $tester->execute(['source' => 'unknown']));
        self::assertStringContainsString('Unknown team source "unknown". Available sources: rapid.', $tester->getDisplay());
    }

    public function testFetchFailureDoesNotPersistPartiallyReadTeams(): void
    {
        $databaseException = new class('Database unavailable') extends \RuntimeException implements DatabaseException {};

        foreach ([new \JsonException('Invalid JSON'), new \UnexpectedValueException('Invalid team'), $databaseException] as $exception) {
            $source = $this->createMock(TeamSourceInterface::class);
            $source->expects(self::once())->method('fetchTeams')->willReturnCallback(
                static function () use ($exception): \Generator {
                    yield new SourceTeamDTO('United', 'logo.png', 33, 48, 1878, false, true, new \DateTime());
                    throw $exception;
                },
            );
            $repository = $this->createMock(TeamRepositoryInterface::class);
            $repository->expects(self::never())->method('findByApiId');
            $repository->expects(self::never())->method('createTeam');
            $tester = new CommandTester(new FetchTeamsCommand(new TeamSourceRegistry(['rapid' => $source]), new ImportTeamsHandler($repository)));

            self::assertSame(Command::FAILURE, $tester->execute(['source' => 'rapid']));
            self::assertStringContainsString('Failed to fetch teams from "rapid": '.$exception->getMessage(), $tester->getDisplay());
        }
    }

    public function testEmptySourceSucceedsWithoutCreatingTeams(): void
    {
        $source = $this->createMock(TeamSourceInterface::class);
        $source->expects(self::once())->method('fetchTeams')->willReturn([]);
        $repository = $this->createMock(TeamRepositoryInterface::class);
        $repository->expects(self::never())->method('findByApiId');
        $repository->expects(self::never())->method('createTeam');
        $tester = new CommandTester(new FetchTeamsCommand(new TeamSourceRegistry(['rapid' => $source]), new ImportTeamsHandler($repository)));

        self::assertSame(Command::SUCCESS, $tester->execute(['source' => 'rapid']));
        self::assertStringContainsString('Fetched 0 teams from "rapid".', $tester->getDisplay());
    }

    public function testNonStringSourceIsInvalid(): void
    {
        $repository = $this->createMock(TeamRepositoryInterface::class);
        $repository->expects(self::never())->method('createTeam');
        $tester = new CommandTester(new FetchTeamsCommand(new TeamSourceRegistry([]), new ImportTeamsHandler($repository)));

        self::assertSame(Command::INVALID, $tester->execute(['source' => 42]));
        self::assertStringContainsString('The source argument must be a string.', $tester->getDisplay());
    }
}
