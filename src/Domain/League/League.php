<?php

declare(strict_types=1);

namespace App\Domain\League;

class League
{
    private int $id;
    private string $name;
    private string $leagueNameSlugged;
    private string $log;
    private int $leagueApiId;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLeagueNameSlugged(): string
    {
        return $this->leagueNameSlugged;
    }

    public function setLeagueNameSlugged(string $leagueNameSlugged): self
    {
        $this->leagueNameSlugged = $leagueNameSlugged;

        return $this;
    }

    public function getLog(): string
    {
        return $this->log;
    }

    public function setLog(string $log): self
    {
        $this->log = $log;

        return $this;
    }

    public function getLeagueApiId(): int
    {
        return $this->leagueApiId;
    }

    public function setLeagueApiId(int $leagueApiId): self
    {
        $this->leagueApiId = $leagueApiId;

        return $this;
    }
}
