<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\League\League;
use App\Domain\User\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $league = new League();
        $league->setName('Premier League');
        $league->setLeagueNameSlugged('premier-league');
        $league->setLeagueApiId(123);
        $league->setLogo('premier-league-logo.png');

        $manager->persist($league);

        $user = new User();
        $user->setUsername('demo');
        $user->setEmail('demo@example.com');
        $user->setActive(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'demo-password'));

        $manager->persist($user);
        $manager->flush();
    }
}
