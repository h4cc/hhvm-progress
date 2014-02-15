<?php


namespace h4cc\HHVMProgressBundle\Services;


use h4cc\HHVMProgressBundle\Entity\PackageVersionRepository;
use Packagist\Api\Result\Package\Version;
use Psr\Log\LoggerInterface;

class PackageUpdater
{
    /**
     * @var PackagistApi
     */
    private $packagist;
    /**
     * @var \h4cc\HHVMProgressBundle\Entity\PackageVersionRepository
     */
    private $versions;
    /**
     * @var TravisFetcher
     */
    private $fetcher;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(PackagistApi $packagist, PackageVersionRepository $versions, TravisFetcher $fetcher, LoggerInterface $logger) {
        $this->packagist = $packagist;
        $this->versions = $versions;
        $this->fetcher = $fetcher;
        $this->logger = $logger;
    }

    public function updatePackage($name) {
        try {
            $infos = $this->packagist->getInfosByName($name);
            foreach($infos->getVersions() as $version) {
                $this->updatePackageVersion($name, $version);
            }
        }catch(\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->logger->debug($e);

            throw $e;
        }
    }

    protected function updatePackageVersion($name, Version $version) {
        if($this->needToUpdatePackageVersion($name, $version)) {
            // Fetch status
            $hhvmStatus = $this->fetcher->fetchTravisHHVMStatus($version);
            // Add Version
            $this->storePackageVersion($name, $version, $hhvmStatus);
        }
    }

    protected function storePackageVersion($name, Version $version, $hhvmStatus) {
        $versionNumber = $version->getVersionNormalized();
        if($hhvmStatus) {
            // Remove a name/version previous, because the git_reference might have changed.
            $this->versions->removeByNameAndVersion($name, $versionNumber);

            $this->logger->info("Adding $name@$versionNumber with hhvmStatus $hhvmStatus");
            $this->versions->add($name, $version->getType(), $version->getDescription(), $versionNumber, $version->getSource()->getReference(), $hhvmStatus);
        }else{
            $this->logger->info("No hhvmStatus info found for $name@$versionNumber");
        }
    }

    protected function needToUpdatePackageVersion($name, Version $version) {

        // Check if "type" is not yet set.
        $packageVersion = $this->versions->get($name, $version->getVersionNormalized());
        if($packageVersion && !$packageVersion->getType()) {
            $this->logger->info("Need to update $name, because of missing type tag");

            return true;
        }

        return ! $this->versions->exists(
          $name,
          $version->getVersionNormalized(),
          $version->getSource()->getReference()
        );
    }
}
 