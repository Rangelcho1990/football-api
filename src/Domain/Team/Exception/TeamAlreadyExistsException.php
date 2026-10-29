<?php

declare(strict_types=1);

namespace App\Domain\Team\Exception;

final class TeamAlreadyExistsException extends \DomainException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('A team with these details already exists.', 0, $previous);
    }
}
