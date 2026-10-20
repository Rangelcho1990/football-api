<?php

declare(strict_types=1);

namespace App\Tests\Application\League;

use App\Application\League\DTO\SourceLeagueDTO;
use App\Application\League\ImportLeaguesHandler;
use App\Domain\League\Exception\LeagueMissingLogoException;
use App\Domain\League\League;
use App\Domain\League\LeagueRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class ImportLeaguesHandlerTest extends TestCase
{
    private function dto(string $logo = 'https://example.com/logo.png'): SourceLeagueDTO
    {
        return new SourceLeagueDTO('Premier League', 'premier-league', $logo, 'League', 569, 42, 1, new \DateTime());
    }

    public function testSkipsExistingLeagueBeforeLogoValidation(): void
    {
        $repository = $this->createMock(LeagueRepositoryInterface::class);
        $repository->expects(self::once())->method('findByApiId')->with(569)->willReturn(new League());
        $repository->expects(self::never())->method('createLeague');

        (new ImportLeaguesHandler($repository))->handler([$this->dto('')]);
    }

    public function testRejectsMissingLogo(): void
    {
        $repository = $this->createMock(LeagueRepositoryInterface::class);
        $repository->expects(self::once())->method('findByApiId')->with(569)->willReturn(null);
        $repository->expects(self::never())->method('createLeague');

        $this->expectException(LeagueMissingLogoException::class);
        (new ImportLeaguesHandler($repository))->handler([$this->dto('  ')]);
    }

    public function testCreatesLeagueWithAllDtoFields(): void
    {
        $dto = $this->dto();
        $repository = $this->createMock(LeagueRepositoryInterface::class);
        $repository->expects(self::once())->method('findByApiId')->with(569)->willReturn(null);
        $repository->expects(self::once())->method('createLeague')->with(self::callback(
            static function (League $league) use ($dto): bool {
                self::assertSame($dto->leagueName, $league->getName());
                self::assertSame($dto->leagueSlug, $league->getLeagueNameSlugged());
                self::assertSame($dto->leagueLogo, $league->getLogo());
                self::assertSame($dto->leagueType, $league->getLeagueType());
                self::assertSame($dto->leagueApiId, $league->getLeagueApiId());
                self::assertSame($dto->leagueCountryId, $league->getCountryId());
                self::assertSame($dto->leagueStatus, $league->getStatus());
                self::assertSame($dto->leagueLastUpdated, $league->getLastUpdated());

                return true;
            },
        ));

        (new ImportLeaguesHandler($repository))->handler([$dto]);
    }
}
