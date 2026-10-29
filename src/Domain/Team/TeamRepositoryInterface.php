<?php

declare(strict_types=1);

namespace App\Domain\Team;

use App\Domain\Team\Exception\TeamAlreadyExistsException;

interface TeamRepositoryInterface
{
    public function findByApiId(int $apiId): ?Team;

    /** @throws TeamAlreadyExistsException */
    public function createTeam(Team $team): void;
}
