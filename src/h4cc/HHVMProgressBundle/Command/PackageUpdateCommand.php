<?php


namespace h4cc\HHVMProgressBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PackageUpdateCommand extends ContainerAwareCommand
{
    private $output;

    protected function configure()
    {
        $this
            ->setName('h4cc:hhvm:package:update')
            ->setDescription('Updates the index')
            ->addOption('number-of-packages', 'p', InputOption::VALUE_OPTIONAL, 'Number of Packages to update. "0" to disable.', 200)
            ->addOption('local', null, InputOption::VALUE_NONE, 'Only test a fixed list of local packages');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;

        $this->local = $this->input->getOption('local');

        if(!$this->local) {
            $this->updatePackagesFromPackagistFeed();
        }

        $this->updatePackagesAtRandom();
    }

    protected function updatePackagesFromPackagistFeed()
    {
        $feeds = $this->getContainer()->get('h4cc_hhvm_progress.feeds.packagist');

        $names = $feeds->getRecentPackageNames();

        $this->output->writeln('Updating Packages from Feed: '.count($names));
        $this->updatedPackagesByNames($names);
    }

    protected function updatePackagesAtRandom()
    {
        if($this->local) {
            // Packages for testing all three possible hhvm status.
            $packages = array(
                'symfony/symfony',
                'nelmio/alice',
                'h4cc/alice-fixtures-bundle',
                'doctrine/lexer',
                'behat/behat',
                'behat/mink',
                'composer/composer',
                'clue/graph',
                'doctrine/common',
            );

            $this->removePackagesRemovedByPackagistByNames($packages);

        }else{
            $packagist = $this->getContainer()->get('h4cc_hhvm_progress.api.packagist');
            $packages = $packagist->getAllPackageNames();

            $this->removePackagesRemovedByPackagistByNames($packages);

            // Shuffle packages as a update strategy :)
            shuffle($packages);

            // Apply argument
            if($nop = $this->input->getOption('number-of-packages')) {
                $packages = array_slice($packages, 0, $nop);
            }
        }

        $this->output->writeln('Updating Random Packages: '.count($packages));
        $this->updatedPackagesByNames($packages);
    }

    protected function removePackagesRemovedByPackagistByNames(array $names) {
        /** @var \h4cc\HHVMProgressBundle\Services\PackageUpdater $updater */
        $updater = $this->getContainer()->get('h4cc_hhvm_progress.package.updater');

        $updater->syncPackageNames($names);
    }

    protected function updatedPackagesByNames(array $names) {
        /** @var \h4cc\HHVMProgressBundle\Services\PackageUpdater $updater */
        $updater = $this->getContainer()->get('h4cc_hhvm_progress.package.updater');

        foreach($names as $package) {
            $this->output->writeln("Updating package: ".$package);
            $updater->updatePackage($package);
        }
    }
}