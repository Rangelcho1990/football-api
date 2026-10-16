<?php

declare(strict_types=1);

namespace App\Application\User\DTO;

final readonly class UserRequestDTO
{
    public function __construct(
        public string $username,
        public string $email,
        public string $password,
        public int $avatar,
    ) {
    }
}
