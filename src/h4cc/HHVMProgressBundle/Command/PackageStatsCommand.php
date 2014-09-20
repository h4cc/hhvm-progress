<?php


namespace h4cc\HHVMProgressBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PackageStatsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setName('h4cc:hhvm:package_stats')
        ->setDescription('Creates stats for packages');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()
            ->get('h4cc_hhvm_progress.package.stats')
            // Date since when packages on packagist have ben published.
            ->fetchStatsFromDate(new \DateTime('2011-10-15'));
    }
}