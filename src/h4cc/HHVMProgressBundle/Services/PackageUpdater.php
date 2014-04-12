<?php


namespace h4cc\HHVMProgressBundle\Services;


use h4cc\HHVMProgressBundle\Entity\PackageVersion;
use h4cc\HHVMProgressBundle\Entity\PackageVersionRepository;
use h4cc\HHVMProgressBundle\Exception\GithubAuthErrorException;
use h4cc\HHVMProgressBundle\Exception\GithubRateLimitException;
use Packagist\Api\Result\Package\Version;
use Packagist\Api\Result\Package;
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

            $this->deleteAllVersionsForPackageThatDoesNotExistAnymore($infos);

            foreach($infos->getVersions() as $version) {
                $this->updatePackageVersion($name, $version);
            }
        }catch(GithubRateLimitException $e) {
            // This is a global error, can not proceed.
            throw $e;
        }catch(GithubAuthErrorException $e) {
            // This is a global error, can not proceed.
            throw $e;
        }catch(\Exception $e) {
            // Updating this package failed.
            $this->logger->error($e->getMessage());
            $this->logger->debug($e);
        }
    }

    protected function deleteAllVersionsForPackageThatDoesNotExistAnymore(Package $package) {
        // List all versions
        $versions = array();
        foreach($package->getVersions() as $version) {
            $versions[] = $version->getVersionNormalized();
        }

        // Select all package versions that are not in given versions for package.
        $versionsForDeletion = $this->versions->getAllForNameWhereVersionNot($package->getName(), $versions);

        // Delete all versions
        foreach($versionsForDeletion as $versionToDelete) {
            $this->logger->info("Deleting version ".$versionToDelete->getName()." @ ".$version->getVersion().", because packagist does not list it anymore.");

            $this->versions->remove($versionToDelete);
        }
    }

    protected function updatePackageVersion($name, Version $version) {
        if($this->needToUpdatePackageVersion($name, $version)) {
            // Fetch status
            $hhvmStatus = $this->fetcher->fetchTravisHHVMStatus($version);
            $this->logger->debug("Fetched HHVM status ".(int)$hhvmStatus." for $name");
            if($hhvmStatus) {
                // Add Version
                $this->storePackageVersion($name, $version, $hhvmStatus, $this->fetcher->getTravisFileContent());
            }else{
                $this->logger->info("No hhvmStatus info found for $name@".$version->getVersion());
            }
        }
    }

    protected function storePackageVersion($name, Version $version, $hhvmStatus, $travisContent) {
        $versionNumber = $version->getVersionNormalized();

        // Remove a name/version previous, because the git_reference might have changed.
        $this->versions->removeByNameAndVersion($name, $versionNumber);

        $this->logger->info("Adding $name@$versionNumber with hhvmStatus $hhvmStatus");
        $this->versions->add(
          $name,
          $version->getType(),
          $version->getDescription(),
          $versionNumber,
          $version->getSource()->getReference(),
          $hhvmStatus,
          $travisContent
        );
    }

    protected function needToUpdatePackageVersion($name, Version $version) {

        $packageVersion = $this->versions->get($name, $version->getVersionNormalized());

        if($packageVersion) {
            // Check if "type" is not yet set.
            if(!$packageVersion->getType()) {
                $this->logger->info("Need to update $name @ ".$version->getVersionNormalized().", because of missing type tag");

                return true;
            }

            // Fetch travisContent if there should be some.
            if(!$packageVersion->getTravisContent() && $packageVersion->getHhvmStatus() >= PackageVersion::HHVM_STATUS_NONE) {
                $this->logger->info("Need to update $name @ ".$version->getVersionNormalized().", because of missing travis content");

                return true;
            }
        }

        return ! $this->versions->exists(
          $name,
          $version->getVersionNormalized(),
          $version->getSource()->getReference()
        );
    }
}
 