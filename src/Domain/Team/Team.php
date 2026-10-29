<?php

declare(strict_types=1);

namespace App\Domain\Team;

class Team
{
    private int $id;
    private string $name;
    private ?string $logo = null;
    private int $teamApiId;
    private int $countryId;
    private ?int $founded = null;
    private ?bool $national = null;
    private ?bool $status = true;
    private ?\DateTime $lastUpdated = null;

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

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    public function getTeamApiId(): int
    {
        return $this->teamApiId;
    }

    public function setTeamApiId(int $teamApiId): self
    {
        $this->teamApiId = $teamApiId;

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

    public function getFounded(): ?int
    {
        return $this->founded;
    }

    public function setFounded(?int $founded): self
    {
        $this->founded = $founded;

        return $this;
    }

    public function getNational(): ?bool
    {
        return $this->national;
    }

    public function setNational(?bool $national): self
    {
        $this->national = $national;

        return $this;
    }

    public function getStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(?bool $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getLastUpdated(): ?\DateTime
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(?\DateTime $lastUpdated): self
    {
        $this->lastUpdated = $lastUpdated;

        return $this;
    }
}
