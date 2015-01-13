<?hh // strict

namespace h4cc\HHVMProgressBundle\Services;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ReplacesUpdater
{
    private PackagistApi $packagist;
    private LoggerInterface $logger;
    private String $cacheDir;

    private $cachePathSerialized;
    private $cachePathPHP;

    public function __construct(
        PackagistApi $packagist,
        String $cacheDir
    )
    {
        $this->logger = new NullLogger();

        $this->packagist = $packagist;
        $this->cacheDir = $cacheDir;

        $this->cachePathSerialized = $this->cacheDir .'/packagist_package_infos.serialized';
        $this->cachePathPHP = $this->cacheDir .'/packagist_package_infos.php';
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function updateReplaces()
    {
        $packageNames = $this->packagist->getAllPackageNames();
        $namesCount = count($packageNames);
        $packageInfos = [];
        $i = 1;
        foreach($packageNames as $name) {
            $this->logger->debug('Fetching info for '.$name.' from packagist '.$i++.'/'.$namesCount);
            $packageInfos[$name] = $this->packagist->getInfosByName($name);
        }

        file_put_contents($this->cachePathSerialized, serialize($packageInfos));
        $this->createReplacesMapFromSerializedFile();
    }

    private function createReplacesMapFromSerializedFile()
    {
        $replacedPackages = [];

        if(file_exists($this->cachePathSerialized)) {
            $data = unserialize(file_get_contents($this->cachePathSerialized));
            foreach($data as $package) {
                foreach($package->getVersions() as $version) {
                    $replaced = $version->getReplace();
                    if(is_array($replaced)) {
                        foreach($replaced as $replacedName => $replacedVersion) {

                            if('self.version' == $replacedVersion) {
                                // Using concrete version instead of alias.
                                $replacedVersion = $version->getVersion();
                            }

                            if(!$this->isStaticVersion($version->getVersion()) || !$this->isStaticVersion($replacedVersion)) {
                                // Ignoring replacements with dynamic versions for now.
                                continue;
                            }

                            if($package->getName() != $replacedName || $version->getVersion() != $replacedVersion) {
                                // Avoid packages that replace themselve.

                                $replacedPackages[$replacedName][$replacedVersion][] = array(
                                    'name' => $package->getName(),
                                    'version' => $version->getVersion(),
                                );
                            }
                        }
                    }
                }
            }
        }

        file_put_contents($this->cachePathPHP, '<?php return '.var_export($replacedPackages, true).';');
    }

    private function isStaticVersion($version) {
        if(stripos($version, '*') || stripos($version, '~') || stripos($version, '>') || stripos($version, '<')) {
            // Containing a version operator means non static.
            return false;
        }

        return true;
    }
}
