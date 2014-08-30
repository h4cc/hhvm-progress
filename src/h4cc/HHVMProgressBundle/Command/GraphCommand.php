<?php


namespace h4cc\HHVMProgressBundle\Command;

use h4cc\HHVMProgressBundle\Graph\GraphComposer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class GraphCommand extends ContainerAwareCommand
{
    const TMP_DIR = '/tmp/hhvm_h4cc';

    protected function configure()
    {
        $this
          ->setName('h4cc:hhvm:graph')
          ->setDescription('Updates the graphs of major frameworks.');
    }

    protected function getPHPBinary()
    {
        return $this->getContainer()->getParameter('php_binary');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sfVersions = array(
          '2.3.*' => 'symfony_2_3',
          '2.4.*' => 'symfony_2_4',
          '2.5.*' => 'symfony_2_5',
        );

        foreach ($sfVersions as $version => $name) {
            try {
                $this->createGraphSymfony2($version, $name);
            } catch (\Exception $e) {
                $output->writeln("Generating Symfony2 graphs failed: " . $e->getMessage());
            }
        }

        $laravelVersions = array(
          '4.0.*' => 'laravel_4_0',
          '4.1.*' => 'laravel_4_1',
          '4.2.*' => 'laravel_4_2',
        );

        foreach ($laravelVersions as $version => $name) {
            try {
                $this->createGraphLaravel4($version, $name);
            } catch (\Exception $e) {
                $output->writeln("Generating Laravel graphs failed: " . $e->getMessage());
            }
        }

        $silexVersions = array(
          '1.0.*' => 'silex_1_0',
          '1.1.*' => 'silex_1_1',
          '1.2.*' => 'silex_1_2',
        );

        foreach ($silexVersions as $version => $name) {
            try {
                $this->createGraphSilex($version, $name);
            } catch (\Exception $e) {
                $output->writeln("Generating Silex graphs failed: " . $e->getMessage());
            }
        }

        /*
        // Damn, thats slooooow.
        $drupalVersions = array(
          '8.*@dev' => 'drupal_8_master',
        );

        foreach ($drupalVersions as $version => $name) {
            try {
                $this->createGraphDrupal($version, $name);
            } catch (\Exception $e) {
                $output->writeln("Generating Drupal graphs failed: " . $e->getMessage());
            }
        }
        */

        /*
        $fuelphpVersions = array(
          'dev-master' => 'fuelphp_master',
        );

        foreach ($fuelphpVersions as $version => $name) {
            try {
                $this->createGraphFuelPHP($version, $name);
            } catch (\Exception $e) {
                $output->writeln("Generating FuelPHP graphs failed: " . $e->getMessage());
            }
        }

        $ppiVersions = array(
          'dev-master' => 'ppi_master',
        );

        foreach ($ppiVersions as $version => $name) {
            try {
                $this->createGraphPPI($version, $name);
            } catch (\Exception $e) {
                $output->writeln("Generating PPI graphs failed: " . $e->getMessage());
            }
        }
        */

        $p = $this->process(self::TMP_DIR);
        $p('rm -rf ' . self::TMP_DIR);
    }

    protected function createGraphFuelPHP($version, $name)
    {
        $this->prepare();
        $p = $this->process(self::TMP_DIR);

        file_put_contents(
          self::TMP_DIR . '/composer.json',
          '{ "name": "fuelphp/fuelphp", "require": {   "fuelphp/foundation": "' . $version . '"  }, "minimum-stability": "dev"}'
        );
        $p($this->getPHPBinary().' composer.phar update --prefer-dist');

        $this->createComposerGraphs(self::TMP_DIR, $name);
    }

    protected function createGraphPPI($version, $name)
    {
        $this->prepare();
        $p = $this->process(self::TMP_DIR);

        file_put_contents(
          self::TMP_DIR . '/composer.json',
          '{ "require": {   "ppi/framework": "' . $version . '"  }}'
        );
        $p($this->getPHPBinary().' composer.phar update --prefer-dist');

        $this->createComposerGraphs(self::TMP_DIR, $name);
    }

    protected function createGraphDrupal($version, $name)
    {
        $this->prepare();
        $p = $this->process(self::TMP_DIR);

        file_put_contents(
          self::TMP_DIR . '/composer.json',
          '{ "require": {   "drupal/drupal": "' . $version . '"  }}'
        );
        $p($this->getPHPBinary().' composer.phar update --prefer-dist');

        $this->createComposerGraphs(self::TMP_DIR, $name);
    }

    protected function createGraphSilex($version, $name)
    {
        $this->prepare();
        $p = $this->process(self::TMP_DIR);

        file_put_contents(
          self::TMP_DIR . '/composer.json',
          '{ "name": "root", "require": {   "silex/silex": "' . $version . '"  }}'
        );
        $p($this->getPHPBinary().' composer.phar install --prefer-dist');

        $this->createComposerGraphs(self::TMP_DIR, $name);
    }

    protected function createGraphLaravel4($version, $name)
    {
        $this->prepare();
        $p = $this->process(self::TMP_DIR);

        file_put_contents(
          self::TMP_DIR . '/composer.json',
          '{ "name": "laravel/laravel", "require": {   "laravel/framework": "' . $version . '"  }}'
        );
        $p($this->getPHPBinary().' composer.phar install --prefer-dist');

        $this->createComposerGraphs(self::TMP_DIR, $name);
    }

    protected function createGraphSymfony2($version, $name)
    {
        $this->prepare();

        $p = $this->process(self::TMP_DIR);
        $p(
            $this->getPHPBinary().' composer.phar create-project --prefer-dist symfony/framework-standard-edition target "' . $version . '"'
        );
        $this->createComposerGraphs(self::TMP_DIR . '/target', $name);
    }

    protected function prepare()
    {
        $p = $this->process(self::TMP_DIR);

        $p('rm -rf ' . self::TMP_DIR);
        $p('mkdir -p ' . self::TMP_DIR);
        $p('curl -s http://getcomposer.org/installer | php');
    }

    protected function createComposerGraphs($dir, $filePrefix)
    {
        $image = $this->createComposerImage($dir);
        $this->writeGraphImage($filePrefix . '.png', $image);

        $image = $this->createComposerImage($dir, true);
        $this->writeGraphImage($filePrefix . '-dev.png', $image);
    }

    protected function writeGraphImage($name, $content)
    {
        $path = $this->getContainer()->getParameter('kernel.root_dir');
        $path = realpath($path . '/../web/graphs/');
        $target = $path . '/' . $name;

        $written = file_put_contents($target, $content);

        if (!$written) {
            throw new \RuntimeException('Could not write image to file.');
        }

        $this->markImage($target);
    }

    protected function markImage($path)
    {
        $im = imagecreatefrompng($path);
        $black = imagecolorallocate($im, 0, 0, 0);
        $font = 5; // font size
        $leftTextPos = 10;
        $text = 'Generated by hhvm.h4cc.de at ' . date('Y.m.d H:i:s');
        imagestring($im, $font, $leftTextPos, 9, $text, $black);
        $result = imagepng($im, $path, 9);
        imagedestroy($im);

        if (!$result) {
            throw new \RuntimeException("Could not write marked graph image.");
        }
    }

    protected function createComposerImage($path, $dev = false)
    {
        if (!file_exists($path . '/composer.json')) {
            throw new \RuntimeException('Missing composer.json');
        }

        if (!file_exists($path . '/composer.lock')) {
            throw new \RuntimeException('Missing composer.lock');
        }

        $composerContent = file_get_contents($path . '/composer.json');
        $composerLockContent = file_get_contents($path . '/composer.lock');

        $graph = new GraphComposer($composerContent, $composerLockContent, $dev);
        $graph->setPackageVersionRepo($this->getContainer()->get('h4cc_hhvm_progress.repos.package_version'));

        $image = $graph->getImage();

        if (!$image) {
            throw new \RuntimeException("Could not generate composer graph image.");
        }

        return $image;
    }

    protected function process($cwd)
    {
        return function ($cmd) use ($cwd) {
            $process = new Process($cmd, $cwd);
            $process->setTimeout(3600);
            $process->run(
              function ($type, $buffer) {
                  if (Process::ERR === $type) {
                      echo 'ERR > ' . $buffer;
                  } else {
                      //echo 'OUT > ' . $buffer;
                  }
              }
            );
            if (!$process->isSuccessful()) {
                throw new \RuntimeException("Command failed $cmd");
            }
        };
    }
}