<?php

declare(strict_types=1);

namespace App\Domain\League\Exception;

final class LeagueAlreadyExistsException extends \DomainException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(
            'A league with these details already exists.',
            0,
            $previous,
        );
    }
}
