<?php


namespace h4cc\HHVMProgressBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReplacesUpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('h4cc:hhvm:replaces:update')
            ->setDescription('Updates the information, which package can be replaced by which.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->printMemoryUsage($output);
        $this->getContainer()->get('h4cc_hhvm_progress.replaces.updater')->updateReplaces();
        $this->printMemoryUsage($output);
    }

    private function printMemoryUsage(OutputInterface $output)
    {
        $usedBytes = memory_get_usage();
        $output->writeln('[MEMORY] Allocated bytes: ' . $usedBytes);
    }
}