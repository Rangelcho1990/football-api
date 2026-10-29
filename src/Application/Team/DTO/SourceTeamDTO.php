<?php

declare(strict_types=1);

namespace App\Application\Team\DTO;

final readonly class SourceTeamDTO
{
    public function __construct(
        public string $teamName,
        public ?string $teamLogo,
        public int $teamApiId,
        public int $teamCountryId,
        public ?int $teamFounded,
        public ?bool $teamNational,
        public ?bool $teamStatus,
        public ?\DateTime $teamLastUpdated,
    ) {
    }
}
