<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use App\Domain\Team\Exception\TeamAlreadyExistsException;
use App\Domain\Team\Team;
use App\Domain\Team\TeamRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Team> */
class TeamRepository extends ServiceEntityRepository implements TeamRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    public function findByApiId(int $apiId): ?Team
    {
        return $this->findOneBy(['teamApiId' => $apiId]);
    }

    public function createTeam(Team $team): void
    {
        try {
            $entityManager = $this->getEntityManager();
            $entityManager->persist($team);
            $entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw new TeamAlreadyExistsException(previous: $exception);
        }
    }
}
