<?php


namespace h4cc\HHVMProgressBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StatusUpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
          ->setName('h4cc:hhvm:status:update')
          ->setDescription('Will recalculate the HHVM status for ALL stored travis files.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $updater = $this->getContainer()->get('h4cc_hhvm_progress.travis_status_updater');
        $updater->updateAllStatus();
    }
}