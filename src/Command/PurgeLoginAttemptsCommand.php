<?php

namespace App\Command;

use App\Repository\LoginAttemptRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Je supprime les anciennes tentatives de connexion de la base de donnees.
 * Cette commande peut etre executee via un cron job quotidien.
 *
 * Exemple : php bin/console app:purge-login-attempts --days=7
 */
#[AsCommand(
    name: 'app:purge-login-attempts',
    description: 'Supprime les anciennes tentatives de connexion de la base de données'
)]
final class PurgeLoginAttemptsCommand extends Command
{
    public function __construct(
        private readonly LoginAttemptRepository $loginAttemptRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'days',
            'd',
            InputOption::VALUE_OPTIONAL,
            'Nombre de jours à conserver',
            7
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');

        if ($days < 1) {
            $io->error('Le nombre de jours doit être supérieur à 0.');
            return Command::FAILURE;
        }

        $deleted = $this->loginAttemptRepository->purgeOldAttempts($days);

        $io->success(sprintf(
            '%d tentative(s) de connexion supprimée(s) (plus anciennes que %d jour(s)).',
            $deleted,
            $days
        ));

        return Command::SUCCESS;
    }
}