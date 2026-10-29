<?php

declare(strict_types=1);

namespace App\Application\Team;

use App\Application\Team\DTO\SourceTeamDTO;
use App\Domain\Team\Team;
use App\Domain\Team\TeamRepositoryInterface;

readonly class ImportTeamsHandler
{
    public function __construct(private TeamRepositoryInterface $teamRepository)
    {
    }

    /** @param iterable<SourceTeamDTO> $sourceTeamDTO */
    public function handler(iterable $sourceTeamDTO): void
    {
        foreach ($sourceTeamDTO as $dto) {
            if (null !== $this->teamRepository->findByApiId($dto->teamApiId)) {
                continue;
            }

            $team = (new Team())
                ->setName($dto->teamName)
                ->setLogo($dto->teamLogo)
                ->setTeamApiId($dto->teamApiId)
                ->setCountryId($dto->teamCountryId)
                ->setFounded($dto->teamFounded)
                ->setNational($dto->teamNational)
                ->setStatus($dto->teamStatus)
                ->setLastUpdated($dto->teamLastUpdated);

            $this->teamRepository->createTeam($team);
        }
    }
}
