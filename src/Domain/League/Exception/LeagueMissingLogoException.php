<?php

declare(strict_types=1);

namespace App\Domain\League\Exception;

final class LeagueMissingLogoException extends \DomainException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(
            'Missing league logo!',
            0,
            $previous,
        );
    }
}
