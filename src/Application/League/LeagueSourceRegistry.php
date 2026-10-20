<?php

declare(strict_types=1);

namespace App\Application\League;

final readonly class LeagueSourceRegistry
{
    /** @var array<string, LeagueSourceInterface> */
    private array $sources;

    /** @param iterable<string, LeagueSourceInterface> $sources */
    public function __construct(iterable $sources)
    {
        $this->sources = is_array($sources) ? $sources : iterator_to_array($sources);
    }

    public function get(string $name): LeagueSourceInterface
    {
        return $this->sources[$name] ?? throw new \InvalidArgumentException(sprintf('Unknown league source "%s". Available sources: %s.', $name, implode(', ', array_keys($this->sources))));
    }
}
