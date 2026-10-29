<?php

declare(strict_types=1);

namespace App\Application\Team;

use App\Application\Team\DTO\SourceTeamDTO;

interface TeamSourceInterface
{
    /** @return iterable<SourceTeamDTO> */
    public function fetchTeams(): iterable;
}
