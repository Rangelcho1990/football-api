<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\User\Exception\UserAlreadyExistsException;

interface UserRepositoryInterface
{
    /**
     * @throws UserAlreadyExistsException
     */
    public function createUser(User $user): void;
}
