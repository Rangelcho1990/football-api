<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\LeagueSource;

use App\Infrastructure\LeagueSource\RapidLeagueSource;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;

final class RapidLeagueSourceTest extends TestCase
{
    public function testFetchesRowsAndReleasesResult(): void
    {
        $payload = [
            'league' => ['id' => 569, 'name' => 'Premier League', 'type' => 'League', 'logo' => 'https://example.com/569.png'],
            'seasons' => [],
        ];
        $rows = [
            ['data' => json_encode([$payload, $payload], JSON_THROW_ON_ERROR), 'country_id' => '42'],
        ];
        $result = $this->createMock(Result::class);
        $result->expects(self::once())->method('iterateAssociative')->willReturn(new \ArrayIterator($rows));
        $result->expects(self::once())->method('free');

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('executeQuery')
            ->with('SELECT data, country_id FROM __rapid_leagues')->willReturn($result);

        $before = new \DateTime();
        $leagues = iterator_to_array((new RapidLeagueSource($connection))->fetchLeagues(), false);
        self::assertCount(2, $leagues);
        self::assertSame('Premier League', $leagues[0]->leagueName);
        self::assertSame('premier-league', $leagues[0]->leagueSlug);
        self::assertSame(569, $leagues[0]->leagueApiId);
        self::assertSame('League', $leagues[0]->leagueType);
        self::assertSame($payload['league']['logo'], $leagues[0]->leagueLogo);
        self::assertSame(42, $leagues[0]->leagueCountryId);
        self::assertSame(42, $leagues[1]->leagueCountryId);
        self::assertSame(1, $leagues[0]->leagueStatus);
        self::assertGreaterThanOrEqual($before, $leagues[0]->leagueLastUpdated);
        self::assertLessThanOrEqual(new \DateTime(), $leagues[0]->leagueLastUpdated);
    }

    public function testReleasesResultWhenReadingFails(): void
    {
        $result = $this->createMock(Result::class);
        $result->expects(self::once())->method('iterateAssociative')->willReturnCallback(
            static function (): \Generator {
                yield ['data' => '[]', 'country_id' => 42];
                throw new \RuntimeException('Read failed');
            },
        );
        $result->expects(self::once())->method('free');

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('executeQuery')->willReturn($result);

        $this->expectException(\RuntimeException::class);
        iterator_to_array((new RapidLeagueSource($connection))->fetchLeagues());
    }

    public function testRejectsMissingCountryIdAndReleasesResult(): void
    {
        foreach ([[], ['country_id' => null], ['country_id' => ''], ['country_id' => ' ']] as $row) {
            $result = $this->createMock(Result::class);
            $result->expects(self::once())->method('iterateAssociative')
                ->willReturn(new \ArrayIterator([$row + ['data' => '[]']]));
            $result->expects(self::once())->method('free');

            $connection = $this->createMock(Connection::class);
            $connection->expects(self::once())->method('executeQuery')->willReturn($result);

            try {
                iterator_to_array((new RapidLeagueSource($connection))->fetchLeagues());
                self::fail('Expected an error for a missing country ID.');
            } catch (\UnexpectedValueException $exception) {
                self::assertSame('Rapid league country ID is missing.', $exception->getMessage());
            }
        }
    }
}
