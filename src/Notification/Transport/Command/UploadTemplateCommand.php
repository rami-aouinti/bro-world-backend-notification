<?php

declare(strict_types=1);

namespace App\Notification\Transport\Command;

use App\Notification\Application\Service\MailjetTemplateService;
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
    name: 'app:notification:upload-templates',
    description: 'Upload Templates',
)]
class UploadTemplateCommand extends Command
{
    public function __construct(
        private readonly MailjetTemplateService $templateService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->templateService->fetchAndStoreTemplates();
            $output->writeln('<info>✅ Templates uploaded !</info>');

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $output->writeln('<error>❌ Error : ' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }
    }
}
