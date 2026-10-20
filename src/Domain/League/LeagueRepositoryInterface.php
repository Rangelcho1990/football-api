<?php

declare(strict_types=1);

namespace App\Domain\League;

use App\Domain\League\Exception\LeagueAlreadyExistsException;

interface LeagueRepositoryInterface
{
    public function findByApiId(int $apiId): ?League;

    /**
     * @throws LeagueAlreadyExistsException
     */
    public function createLeague(League $league): void;
}
