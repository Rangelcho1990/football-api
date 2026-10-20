<?php

declare(strict_types=1);

namespace App\Infrastructure\Commands;

use App\Application\League\ImportLeaguesHandler;
use App\Application\League\LeagueSourceRegistry;
use Doctrine\DBAL\Exception as DatabaseException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:import-leagues')]
class FetchLeaguesCommand extends Command
{
    public function __construct(
        private readonly LeagueSourceRegistry $leagueSourceRegistry,
        private readonly ImportLeaguesHandler $importLeaguesHandler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'source',
            InputArgument::REQUIRED,
            'League source to import from (for example, rapid)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('source');
        if (!is_string($name)) {
            $io->error('The source argument must be a string.');

            return Command::INVALID;
        }

        try {
            $source = $this->leagueSourceRegistry->get($name);
        } catch (\InvalidArgumentException $exception) {
            $io->error($exception->getMessage());

            return Command::INVALID;
        }

        try {
            $leagues = [];
            foreach ($source->fetchLeagues() as $league) {
                $leagues[] = $league;
            }
        } catch (DatabaseException|\JsonException|\UnexpectedValueException $exception) {
            $io->error(sprintf('Failed to fetch leagues from "%s": %s', $name, $exception->getMessage()));

            return Command::FAILURE;
        }

        $this->importLeaguesHandler->handler($leagues);

        $io->success(sprintf('Fetched %d leagues from "%s".', count($leagues), $name));

        return Command::SUCCESS;
    }
}
