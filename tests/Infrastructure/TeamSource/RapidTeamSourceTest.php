<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\TeamSource;

use App\Infrastructure\Services\TeamSource\RapidTeamSource;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RapidTeamSourceTest extends TestCase
{
    public function testReadsMultipleRowsAndReleasesResult(): void
    {
        $rows = [
            ['country_id' => '48', 'data' => '[{"team":{"id":33,"name":"Manchester United","logo":"https://example.com/33.png","founded":1878,"national":false,"country":"Ignored country"},"venue":{"id":556}}]'],
            ['country_id' => 48, 'data' => '[]'],
            ['country_id' => 49, 'data' => '[{"team":{"id":34,"name":"Newcastle","logo":null,"founded":null,"national":true}}]'],
        ];
        $before = new \DateTime();
        $teams = iterator_to_array($this->source($rows)->fetchTeams(), false);

        self::assertCount(2, $teams);
        self::assertSame(33, $teams[0]->teamApiId);
        self::assertSame('Manchester United', $teams[0]->teamName);
        self::assertSame('https://example.com/33.png', $teams[0]->teamLogo);
        self::assertSame(48, $teams[0]->teamCountryId);
        self::assertSame(1878, $teams[0]->teamFounded);
        self::assertFalse($teams[0]->teamNational);
        self::assertTrue($teams[0]->teamStatus);
        self::assertGreaterThanOrEqual($before, $teams[0]->teamLastUpdated);
        self::assertLessThanOrEqual(new \DateTime(), $teams[0]->teamLastUpdated);
        self::assertSame(34, $teams[1]->teamApiId);
        self::assertSame(49, $teams[1]->teamCountryId);
        self::assertNull($teams[1]->teamLogo);
        self::assertNull($teams[1]->teamFounded);
        self::assertTrue($teams[1]->teamNational);
    }

    /** @param class-string<\Throwable> $exceptionClass */
    #[DataProvider('invalidPayloads')]
    public function testRejectsInvalidPayloadsAndReleasesResult(mixed $data, string $exceptionClass): void
    {
        $source = $this->source([['country_id' => 48, 'data' => $data]]);

        $this->expectException($exceptionClass);
        iterator_to_array($source->fetchTeams());
    }

    /** @return iterable<string, array{mixed, class-string<\Throwable>}> */
    public static function invalidPayloads(): iterable
    {
        yield 'null data' => [null, \UnexpectedValueException::class];
        yield 'non-string data' => [123, \UnexpectedValueException::class];
        yield 'malformed JSON' => ['{', \JsonException::class];
        yield 'scalar JSON' => ['null', \UnexpectedValueException::class];
        yield 'empty object' => ['{}', \UnexpectedValueException::class];
        yield 'numeric object keys' => ['{"0":{"team":{"id":33,"name":"United","logo":"logo.png"}}}', \UnexpectedValueException::class];
        yield 'object instead of list' => ['{"team": {"id": 33}}', \UnexpectedValueException::class];
        yield 'invalid entry' => ['[null]', \UnexpectedValueException::class];
        yield 'missing team' => ['[{}]', \UnexpectedValueException::class];
        yield 'string ID' => ['[{"team":{"id":"33","name":"United","logo":"logo.png"}}]', \UnexpectedValueException::class];
        yield 'missing name' => ['[{"team":{"id":33,"logo":"logo.png"}}]', \UnexpectedValueException::class];
        yield 'invalid logo' => ['[{"team":{"id":33,"name":"United","logo":123}}]', \UnexpectedValueException::class];
        yield 'negative API ID' => ['[{"team":{"id":-1,"name":"United"}}]', \UnexpectedValueException::class];
        yield 'overflowing API ID' => ['[{"team":{"id":4294967296,"name":"United"}}]', \UnexpectedValueException::class];
        yield 'invalid founded' => ['[{"team":{"id":33,"name":"United","founded":"1878"}}]', \UnexpectedValueException::class];
        yield 'invalid national' => ['[{"team":{"id":33,"name":"United","national":1}}]', \UnexpectedValueException::class];
    }

    public function testReleasesResultWhenReadingFails(): void
    {
        $result = $this->createMock(Result::class);
        $result->expects(self::once())->method('iterateAssociative')->willReturnCallback(
            static function (): \Generator {
                yield ['country_id' => 48, 'data' => '[]'];
                throw new \RuntimeException('Read failed');
            },
        );
        $result->expects(self::once())->method('free');
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('executeQuery')->willReturn($result);

        $this->expectException(\RuntimeException::class);
        iterator_to_array((new RapidTeamSource($connection))->fetchTeams());
    }

    /** @param list<array<string, mixed>> $rows */
    private function source(array $rows): RapidTeamSource
    {
        $result = $this->createMock(Result::class);
        $result->expects(self::once())->method('iterateAssociative')->willReturn(new \ArrayIterator($rows));
        $result->expects(self::once())->method('free');
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('executeQuery')->with('SELECT data, country_id FROM __rapid_teams')->willReturn($result);

        return new RapidTeamSource($connection);
    }

    public function testRejectsMissingAndInvalidCountryIdsAndReleasesResult(): void
    {
        foreach ([null, '', ' ', 'England', -1, 48.5, true, '2147483648', '999999999999999999999999'] as $countryId) {
            $source = $this->source([['country_id' => $countryId, 'data' => '[]']]);

            try {
                iterator_to_array($source->fetchTeams());
                self::fail('Expected invalid country ID to be rejected.');
            } catch (\UnexpectedValueException $exception) {
                self::assertStringContainsString('Rapid team country ID', $exception->getMessage());
            }
        }
    }

    public function testSupportsMissingOptionalFields(): void
    {
        $teams = iterator_to_array($this->source([
            ['country_id' => 48, 'data' => '[{"team":{"id":4294967295,"name":"United"}}]'],
        ])->fetchTeams());

        self::assertSame(4294967295, $teams[0]->teamApiId);
        self::assertNull($teams[0]->teamLogo);
        self::assertNull($teams[0]->teamFounded);
        self::assertNull($teams[0]->teamNational);
    }
}
