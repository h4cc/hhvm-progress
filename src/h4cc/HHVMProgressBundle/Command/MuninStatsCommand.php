<?php


namespace h4cc\HHVMProgressBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MuninStatsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
        ->setName('h4cc:hhvm:munin_stats')
        ->setDescription('Creates output for Munin')
        ->addArgument('config', InputArgument::OPTIONAL, false);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // show config?
        if ('config' == $input->getArgument('config')) {
            $config[] = 'graph_category HHVM-Support';
            $config[] = 'graph_title HHVM Support for packagist packages';
            $config[] = 'graph_vlabel Count';
            $config[] = 'graph_args -l 0';
            $config[] = 'hhvm_tested.label Tested with HHVM';
            $config[] = 'hhvm_partial_tested.label Partial tested with HHVM';
            $config[] = 'hhvm_not_tested.label Not tested against HHVM';

            $output->writeln($config);

            return;
        }

        $stats = $this->getContainer()->get('h4cc_hhvm_progress.package.stats')->getStatsByHHVMState();

        $output->writeln('hhvm_tested.value ' . $stats['supported']);
        $output->writeln('hhvm_partial_tested.value ' . $stats['allowed_failure']);
        $output->writeln('hhvm_not_tested.value ' . $stats['not_supported']);
    }
}