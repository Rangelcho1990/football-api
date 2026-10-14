<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\DTO\UserRequestDTO;
use App\Domain\User\Exception\UserAlreadyExistsException;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

readonly class CreateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @throws UserAlreadyExistsException
     */
    public function handler(UserRequestDTO $userRequestDTO): void
    {
        $user = new User();
        $user->setUsername($userRequestDTO->username);
        $user->setEmail($userRequestDTO->email);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $userRequestDTO->password)
        );
        $user->setAvatar($userRequestDTO->avatar);
        $user->setActive(true);

        $this->userRepository->createUser($user);
    }
}
