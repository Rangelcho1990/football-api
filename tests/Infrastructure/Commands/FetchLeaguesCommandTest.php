<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Commands;

use App\Application\League\DTO\SourceLeagueDTO;
use App\Application\League\ImportLeaguesHandler;
use App\Application\League\LeagueSourceInterface;
use App\Domain\League\League;
use App\Domain\League\LeagueRepositoryInterface;
use App\Infrastructure\Commands\FetchLeaguesCommand;
use App\Infrastructure\Services\LeagueSource\LeagueSourceRegistry;
use Doctrine\DBAL\Exception as DatabaseException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class FetchLeaguesCommandTest extends TestCase
{
    public function testImportsFromSelectedSource(): void
    {
        $source = $this->createMock(LeagueSourceInterface::class);
        $source->expects(self::once())->method('fetchLeagues')->willReturn([new SourceLeagueDTO('Premier League', 'premier-league', 'logo.png', 'League', 33, 48, 1, new \DateTime())]);
        $otherSource = $this->createMock(LeagueSourceInterface::class);
        $otherSource->expects(self::never())->method('fetchLeagues');
        $repository = $this->createMock(LeagueRepositoryInterface::class);
        $repository->expects(self::once())->method('findByApiId')->with(33)->willReturn(null);
        $repository->expects(self::once())->method('createLeague')->with(self::isInstanceOf(League::class));
        $registry = new LeagueSourceRegistry(new \ArrayIterator(['rapid' => $otherSource, 'test' => $source]));
        $tester = new CommandTester(new FetchLeaguesCommand($registry, new ImportLeaguesHandler($repository)));

        self::assertSame(Command::SUCCESS, $tester->execute(['source' => 'test']));
        self::assertStringContainsString('Fetched 1 leagues from "test".', $tester->getDisplay());
    }

    public function testUnknownSourceDoesNotImport(): void
    {
        $source = $this->createMock(LeagueSourceInterface::class);
        $source->expects(self::never())->method('fetchLeagues');
        $repository = $this->createMock(LeagueRepositoryInterface::class);
        $repository->expects(self::never())->method('findByApiId');
        $repository->expects(self::never())->method('createLeague');
        $tester = new CommandTester(new FetchLeaguesCommand(new LeagueSourceRegistry(['rapid' => $source]), new ImportLeaguesHandler($repository)));

        self::assertSame(Command::INVALID, $tester->execute(['source' => 'unknown']));
        self::assertStringContainsString('Unknown league source "unknown". Available sources: rapid.', $tester->getDisplay());
    }

    public function testFetchFailureDoesNotPersistPartiallyReadLeagues(): void
    {
        $databaseException = new class('Database unavailable') extends \RuntimeException implements DatabaseException {};

        foreach ([new \JsonException('Invalid JSON'), new \UnexpectedValueException('Invalid league'), $databaseException] as $exception) {
            $source = $this->createMock(LeagueSourceInterface::class);
            $source->expects(self::once())->method('fetchLeagues')->willReturnCallback(
                static function () use ($exception): \Generator {
                    yield new SourceLeagueDTO('Premier League', 'premier-league', 'logo.png', 'League', 33, 48, 1, new \DateTime());
                    throw $exception;
                },
            );
            $repository = $this->createMock(LeagueRepositoryInterface::class);
            $repository->expects(self::never())->method('findByApiId');
            $repository->expects(self::never())->method('createLeague');
            $tester = new CommandTester(new FetchLeaguesCommand(new LeagueSourceRegistry(['rapid' => $source]), new ImportLeaguesHandler($repository)));

            self::assertSame(Command::FAILURE, $tester->execute(['source' => 'rapid']));
            self::assertStringContainsString('Failed to fetch leagues from "rapid": '.$exception->getMessage(), $tester->getDisplay());
        }
    }

    public function testEmptySourceSucceedsWithoutCreatingLeagues(): void
    {
        $source = $this->createMock(LeagueSourceInterface::class);
        $source->expects(self::once())->method('fetchLeagues')->willReturn([]);
        $repository = $this->createMock(LeagueRepositoryInterface::class);
        $repository->expects(self::never())->method('findByApiId');
        $repository->expects(self::never())->method('createLeague');
        $tester = new CommandTester(new FetchLeaguesCommand(new LeagueSourceRegistry(['rapid' => $source]), new ImportLeaguesHandler($repository)));

        self::assertSame(Command::SUCCESS, $tester->execute(['source' => 'rapid']));
        self::assertStringContainsString('Fetched 0 leagues from "rapid".', $tester->getDisplay());
    }

    public function testNonStringSourceIsInvalid(): void
    {
        $repository = $this->createMock(LeagueRepositoryInterface::class);
        $repository->expects(self::never())->method('createLeague');
        $tester = new CommandTester(new FetchLeaguesCommand(new LeagueSourceRegistry([]), new ImportLeaguesHandler($repository)));

        self::assertSame(Command::INVALID, $tester->execute(['source' => 42]));
        self::assertStringContainsString('The source argument must be a string.', $tester->getDisplay());
    }
}
