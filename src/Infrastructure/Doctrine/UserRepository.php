<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use App\Domain\User\Exception\UserAlreadyExistsException;
use App\Domain\User\User;
use App\Domain\User\UserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @throws UserAlreadyExistsException
     */
    public function createUser(User $user): void
    {
        try {
            $entityManager = $this->getEntityManager();

            $entityManager->persist($user);
            $entityManager->flush();
        } catch (\Exception $exception) {
            throw new UserAlreadyExistsException(previous: $exception);
        }
    }
}
