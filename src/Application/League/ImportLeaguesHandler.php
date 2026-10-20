<?php

declare(strict_types=1);

namespace App\Application\League;

use App\Application\League\DTO\SourceLeagueDTO;
use App\Domain\League\Exception\LeagueMissingLogoException;
use App\Domain\League\League;
use App\Domain\League\LeagueRepositoryInterface;

readonly class ImportLeaguesHandler
{
    public function __construct(
        private LeagueRepositoryInterface $leagueRepository,
    ) {
    }

    /**
     * @param iterable<SourceLeagueDTO> $sourceLeagueDTO
     */
    public function handler(iterable $sourceLeagueDTO): void
    {
        foreach ($sourceLeagueDTO as $dto) {
            if (null !== $this->leagueRepository->findByApiId($dto->leagueApiId)) {
                continue;
            }

            if ('' === trim($dto->leagueLogo)) {
                throw new LeagueMissingLogoException();
            }

            $league = (new League())
                ->setName($dto->leagueName)
                ->setLeagueNameSlugged($dto->leagueSlug)
                ->setLogo($dto->leagueLogo)
                ->setLeagueApiId($dto->leagueApiId)
                ->setLeagueType($dto->leagueType)
                ->setCountryId($dto->leagueCountryId)
                ->setStatus($dto->leagueStatus)
                ->setLastUpdated($dto->leagueLastUpdated);

            $this->leagueRepository->createLeague($league);
        }
    }
}
