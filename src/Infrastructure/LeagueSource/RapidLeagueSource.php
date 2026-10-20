<?php

declare(strict_types=1);

namespace App\Infrastructure\LeagueSource;

use App\Application\League\DTO\SourceLeagueDTO;
use App\Application\League\LeagueSourceInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\String\Slugger\AsciiSlugger;

readonly class RapidLeagueSource implements LeagueSourceInterface
{
    public function __construct(private Connection $connection)
    {
    }

    /** @return iterable<SourceLeagueDTO>
     * @throws Exception
     */
    public function fetchLeagues(): iterable
    {
        // TODO: add entity and repo?
        $result = $this->connection->executeQuery('SELECT data, country_id FROM __rapid_leagues');
        $slugger = new AsciiSlugger();

        try {
            foreach ($result->iterateAssociative() as $row) {
                $countryId = $row['country_id'] ?? null;
                if (null === $countryId || (is_string($countryId) && '' === trim($countryId))) {
                    throw new \UnexpectedValueException('Rapid league country ID is missing.');
                }

                if (!is_int($countryId) && (!is_string($countryId) || !ctype_digit($countryId))) {
                    throw new \UnexpectedValueException('Rapid league country ID must be an integer.');
                }

                $data = $row['data'] ?? null;
                if (!is_string($data)) {
                    throw new \UnexpectedValueException('Rapid league data must be a JSON string.');
                }

                $leagues = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

                if (!is_array($leagues) || !array_is_list($leagues)) {
                    throw new \UnexpectedValueException('Rapid league data must be a JSON array.');
                }

                foreach ($leagues as $entry) {
                    if (!is_array($entry)) {
                        throw new \UnexpectedValueException('Rapid league data contains an invalid league entry.');
                    }

                    $league = $entry['league'] ?? null;
                    if (!is_array($league)
                        || !is_int($league['id'] ?? null)
                        || !is_string($league['name'] ?? null)
                        || !is_string($league['logo'] ?? null)
                        || !is_string($league['type'] ?? null)
                    ) {
                        throw new \UnexpectedValueException('Rapid league data contains invalid league fields.');
                    }

                    yield new SourceLeagueDTO(
                        leagueName: $league['name'],
                        leagueSlug: $slugger->slug($league['name'])->lower()->toString(),
                        leagueLogo: $league['logo'],
                        leagueType: $league['type'],
                        leagueApiId: $league['id'],
                        leagueCountryId: (int) $countryId,
                        leagueStatus: 1,
                        leagueLastUpdated: new \DateTime(),
                    );
                }
            }
        } finally {
            $result->free();
        }
    }
}
