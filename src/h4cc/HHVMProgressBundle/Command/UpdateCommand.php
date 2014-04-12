<?php


namespace h4cc\HHVMProgressBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
          ->setName('h4cc:hhvm:update')
          ->setDescription('Updates the index')
          ->addOption('number-of-packages', 'p', InputOption::VALUE_OPTIONAL, 'Number of Packages to update. "0" to disable.', 200)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $packagist = $this->getContainer()->get('h4cc_hhvm_progress.api.packagist');
        $packages = $packagist->getAllPackageNames();

        // Shuffle packages as a update strategy :)
        shuffle($packages);

        // Apply argument
        if($nop = $input->getOption('number-of-packages')) {
            $packages = array_slice($packages, 0, $nop);
        }

        /** @var \h4cc\HHVMProgressBundle\Services\PackageUpdater $updater */
        $updater = $this->getContainer()->get('h4cc_hhvm_progress.package.updater');

        /*
        // Packages for testing all three possible hhvm status.
        $packages = array(
            //'symfony/symfony',
            'nelmio/alice',
            'h4cc/alice-fixtures-bundle',
            'doctrine/lexer',
            'behat/behat',
            'behat/mink',
        );
        */

        foreach($packages as $package) {
            $output->writeln("Updating package $package");
            $updater->updatePackage($package);
        }
    }
}