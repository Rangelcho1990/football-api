<?php

declare(strict_types=1);

namespace App\Application\League\DTO;

final class SourceLeagueDTO
{
    public function __construct(
        public string $leagueName,
        public string $leagueSlug,
        public string $leagueLogo,
        public string $leagueType,
        public int $leagueApiId,
        public int $leagueCountryId,
        public int $leagueStatus,
        public \DateTime $leagueLastUpdated,
    ) {
    }
}
