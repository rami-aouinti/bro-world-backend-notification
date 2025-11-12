<?php

declare(strict_types=1);

namespace App\Notification\Transport\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * @package App\Notification\Transport\Command
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
#[AsCommand(
    name: 'app:temporal:create-schedule',
    description: 'Create Schedule Temporal for DailyFetchWorkflow',
)]
class CreateTemporalScheduleCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $output->writeln('<info>✅ Schedule created !</info>');

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $output->writeln('<error>❌ Error : ' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }
    }
}
