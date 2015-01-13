<?hh // strict

namespace h4cc\HHVMProgressBundle\Services;

use h4cc\HHVMProgressBundle\Entity\PackageVersionRepository;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Replaces
{
    private LoggerInterface $logger;
    private PackageVersionRepository $versionRepository;

    private String $cacheDir;
    private String $cachePathPHP;

    public function __construct(
        String $cacheDir,
        PackageVersionRepository $versionRepository
    )
    {
        $this->logger = new NullLogger();
        $this->versionRepository = $versionRepository;

        $this->cacheDir = $cacheDir;
        $this->cachePathPHP = $this->cacheDir .'/packagist_package_infos.php';
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function findReplacingVersion(String $name, String $version)
    {
        $foundPackageVersion= null;
        $maxHhvmStatus = -99;

        foreach($this->getReplacingPackagesFor($name, $version) as $replacement) {
            $packageVersion = $this->versionRepository->getByPackageNameAndVersion($replacement['name'], $replacement['version']);
            if($packageVersion) {
                $hhvmStatus = $packageVersion->getTravisContent()->getHhvmStatus();
                if($hhvmStatus > $maxHhvmStatus) {
                    $maxHhvmStatus = $hhvmStatus;
                    $foundPackageVersion = $packageVersion;
                }
            }
        }

        return $foundPackageVersion;
    }

    public function getReplacingPackagesFor(String $name, String $version)
    {
        $map = $this->getReplacesMap();

        if(!array_key_exists($name, $map)) {
            return array();
        }

        if(!array_key_exists($version, $map[$name])) {
            return array();
        }

        return $map[$name][$version];
    }

    private function getReplacesMap()
    {
        if(!file_exists($this->cachePathPHP)) {
            $this->createReplacesMapFromSerializedFile();
        }

        clearstatcache();
        if(file_exists($this->cachePathPHP)) {
            $cache = include($this->cachePathPHP);
            return $cache;
        }

        $this->logger->warning('ReplacesMap Cache File does not exist: '.$this->cachePathPHP);
        return [];
    }
}
