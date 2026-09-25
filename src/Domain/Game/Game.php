<?php

declare(strict_types=1);

namespace App\Domain\Game;

use App\Domain\League\League;
use App\Domain\Team\Team;

class Game
{
    private int $id;
    private string $score;
    private Team $homeTeam;
    private Team $awayTeam;
    private \DateTimeInterface $gameTime;
    private League $league;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getScore(): string
    {
        return $this->score;
    }

    public function setScore(string $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getHomeTeam(): Team
    {
        return $this->homeTeam;
    }

    public function setHomeTeam(Team $homeTeam): self
    {
        $this->homeTeam = $homeTeam;

        return $this;
    }

    public function getAwayTeam(): Team
    {
        return $this->awayTeam;
    }

    public function setAwayTeam(Team $awayTeam): self
    {
        $this->awayTeam = $awayTeam;

        return $this;
    }

    public function getGameTime(): \DateTimeInterface
    {
        return $this->gameTime;
    }

    public function setGameTime(\DateTimeInterface $gameTime): self
    {
        $this->gameTime = $gameTime;

        return $this;
    }

    public function getLeague(): League
    {
        return $this->league;
    }

    public function setLeague(League $league): self
    {
        $this->league = $league;

        return $this;
    }
}
