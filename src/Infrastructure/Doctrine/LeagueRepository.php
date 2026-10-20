<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use App\Domain\League\Exception\LeagueAlreadyExistsException;
use App\Domain\League\League;
use App\Domain\League\LeagueRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<League>
 */
class LeagueRepository extends ServiceEntityRepository implements LeagueRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, League::class);
    }

    public function findByApiId(int $apiId): ?League
    {
        return $this->findOneBy(['leagueApiId' => $apiId]);
    }

    public function createLeague(League $league): void
    {
        try {
            $entityManager = $this->getEntityManager();

            $entityManager->persist($league);
            $entityManager->flush();
        } catch (\Exception $exception) {
            throw new LeagueAlreadyExistsException(previous: $exception);
        }
    }
}
