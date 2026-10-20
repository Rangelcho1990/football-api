<?php

declare(strict_types=1);

namespace App\Application\League;

use App\Application\League\DTO\SourceLeagueDTO;

interface LeagueSourceInterface
{
    /** @return iterable<SourceLeagueDTO> */
    public function fetchLeagues(): iterable;
}
