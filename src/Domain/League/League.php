<?php

declare(strict_types=1);

namespace App\Domain\League;

class League
{
    private int $id;
    private string $name;
    private string $leagueNameSlugged;
    private string $logo;
    private string $leagueType;
    private int $leagueApiId;
    private int $countryId;
    private int $status;
    private \DateTime $lastUpdated;

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

    public function getLogo(): string
    {
        return $this->logo;
    }

    public function setLogo(string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    public function getLeagueType(): string
    {
        return $this->leagueType;
    }

    public function setLeagueType(string $leagueType): self
    {
        $this->leagueType = $leagueType;

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

    public function getCountryId(): int
    {
        return $this->countryId;
    }

    public function setCountryId(int $countryId): self
    {
        $this->countryId = $countryId;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getLastUpdated(): \DateTime
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(\DateTime $lastUpdated): self
    {
        $this->lastUpdated = $lastUpdated;

        return $this;
    }
}
