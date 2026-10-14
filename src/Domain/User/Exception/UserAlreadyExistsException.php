<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

final class UserAlreadyExistsException extends \DomainException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(
            'A user with these details already exists.',
            0,
            $previous,
        );
    }
}
