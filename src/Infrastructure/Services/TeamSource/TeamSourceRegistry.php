<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\TeamSource;

use App\Application\Team\TeamSourceInterface;

final readonly class TeamSourceRegistry
{
    /** @var array<string, TeamSourceInterface> */
    private array $sources;

    /** @param iterable<string, TeamSourceInterface> $sources */
    public function __construct(iterable $sources)
    {
        $this->sources = is_array($sources) ? $sources : iterator_to_array($sources);
    }

    public function get(string $name): TeamSourceInterface
    {
        return $this->sources[$name] ?? throw new \InvalidArgumentException(sprintf('Unknown team source "%s". Available sources: %s.', $name, implode(', ', array_keys($this->sources))));
    }
}
