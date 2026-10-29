<?php

declare(strict_types=1);

namespace App\Infrastructure\Commands;

use App\Application\Team\ImportTeamsHandler;
use App\Infrastructure\Services\TeamSource\TeamSourceRegistry;
use Doctrine\DBAL\Exception as DatabaseException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:import-teams')]
class FetchTeamsCommand extends Command
{
    public function __construct(
        private readonly TeamSourceRegistry $teamSourceRegistry,
        private readonly ImportTeamsHandler $importTeamsHandler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'source',
            InputArgument::REQUIRED,
            'Team source to import from (for example, rapid)',
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
            $source = $this->teamSourceRegistry->get($name);
        } catch (\InvalidArgumentException $exception) {
            $io->error($exception->getMessage());

            return Command::INVALID;
        }

        try {
            $teams = [];
            foreach ($source->fetchTeams() as $team) {
                $teams[] = $team;
            }
        } catch (DatabaseException|\JsonException|\UnexpectedValueException $exception) {
            $io->error(sprintf('Failed to fetch teams from "%s": %s', $name, $exception->getMessage()));

            return Command::FAILURE;
        }

        $this->importTeamsHandler->handler($teams);

        $io->success(sprintf('Fetched %d teams from "%s".', count($teams), $name));

        return Command::SUCCESS;
    }
}
