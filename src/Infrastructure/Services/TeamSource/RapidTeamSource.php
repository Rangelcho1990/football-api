<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\TeamSource;

use App\Application\Team\DTO\SourceTeamDTO;
use App\Application\Team\TeamSourceInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

readonly class RapidTeamSource implements TeamSourceInterface
{
    public function __construct(private Connection $connection)
    {
    }

    /**
     * @return iterable<SourceTeamDTO>
     *
     * @throws Exception
     */
    public function fetchTeams(): iterable
    {
        $result = $this->connection->executeQuery('SELECT data, country_id FROM __rapid_teams');

        try {
            foreach ($result->iterateAssociative() as $row) {
                $countryId = $row['country_id'] ?? null;
                if (null === $countryId || (is_string($countryId) && '' === trim($countryId))) {
                    throw new \UnexpectedValueException('Rapid team country ID is missing.');
                }

                if ((!is_int($countryId) && (!is_string($countryId) || !ctype_digit($countryId)))
                    || false === filter_var($countryId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2147483647]])
                ) {
                    throw new \UnexpectedValueException('Rapid team country ID must be a nonnegative signed integer.');
                }

                $data = $row['data'] ?? null;
                if (!is_string($data)) {
                    throw new \UnexpectedValueException('Rapid team data must be a JSON string.');
                }

                $teams = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

                if (!is_array($teams) || !array_is_list($teams) || !str_starts_with(ltrim($data), '[')) {
                    throw new \UnexpectedValueException('Rapid team data must be a JSON array.');
                }

                foreach ($teams as $entry) {
                    if (!is_array($entry)) {
                        throw new \UnexpectedValueException('Rapid team data contains an invalid team entry.');
                    }

                    $team = $entry['team'] ?? null;
                    if (!is_array($team)
                        || !is_int($team['id'] ?? null)
                        || $team['id'] < 0
                        || $team['id'] > 4294967295
                        || !is_string($team['name'] ?? null)
                    ) {
                        throw new \UnexpectedValueException('Rapid team data contains invalid team fields.');
                    }

                    $logo = $team['logo'] ?? null;
                    $founded = $team['founded'] ?? null;
                    $national = $team['national'] ?? null;
                    if ((null !== $logo && !is_string($logo))
                        || (null !== $founded && (!is_int($founded) || $founded < -2147483648 || $founded > 2147483647))
                        || (null !== $national && !is_bool($national))
                    ) {
                        throw new \UnexpectedValueException('Rapid team data contains invalid team fields.');
                    }

                    yield new SourceTeamDTO(
                        teamName: $team['name'],
                        teamLogo: $logo,
                        teamApiId: $team['id'],
                        teamCountryId: (int) $countryId,
                        teamFounded: $founded,
                        teamNational: $national,
                        teamStatus: true,
                        teamLastUpdated: new \DateTime(),
                    );
                }
            }
        } finally {
            $result->free();
        }
    }
}
