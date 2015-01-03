<?hh // strict

namespace h4cc\HHVMProgressBundle\Services;

use h4cc\HHVMProgressBundle\Entity\Package;
use h4cc\HHVMProgressBundle\Entity\PackageRepository;
use h4cc\HHVMProgressBundle\Entity\PackageVersion;
use h4cc\HHVMProgressBundle\Entity\PackageVersionRepository;
use h4cc\HHVMProgressBundle\Entity\TravisContent;
use h4cc\HHVMProgressBundle\Entity\TravisContentRepository;

use Packagist\Api\Result\Package as PackageInfo;
use Packagist\Api\Result\Package\Version as VersionInfo;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PackageUpdater
{
    private PackagistApi $packagist;
    private PackageRepository $packages;
    private PackageVersionRepository $versions;
    private TravisContentRepository $travisContents;
    private TravisFetcher $travisFetcher;
    private TravisParser $travisParser;
    private LoggerInterface $logger;

    public function __construct(
        PackagistApi $packagist,
        PackageRepository $packages,
        PackageVersionRepository $versions,
        TravisContentRepository $travisContents,
        TravisFetcher $travisFetcher,
        TravisParser $travisParser
    ) {
        $this->logger = new NullLogger();

        $this->packagist = $packagist;
        $this->packages = $packages;
        $this->versions = $versions;
        $this->travisContents = $travisContents;
        $this->travisFetcher = $travisFetcher;
        $this->travisParser = $travisParser;
    }

    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function updatePackage(string $name)
    {
        $this->logger->debug('Updating Package '.$name);

        $packageInfos = $this->packagist->getInfosByName($name);
        if(!$packageInfos) {
            $this->logger->warn('No Packageinfo for '.$name);

            return;
        }

        // See if a Package Entity exists
        $package = $this->ensurePackageExists($name, $packageInfos);

        // Fetch Versions from packagist
        $versions = $packageInfos->getVersions();

        // Update each version
        foreach($versions as $versionInfo) {
            $this->updateVersionForPackageFromInfo($package, $versionInfo);
        }

        // Delete not existing versions.
        $existingVersions = array_keys($versions);
        $this->removeVersionsForPackageNotInList($package, $existingVersions);
    }

    private function removeVersionsForPackageNotInList($package, $existingVersions) {
        $versions = $this->versions->getByPackage($package);

        foreach($versions as $versionFromDB) {
            if(!in_array($versionFromDB->getVersion(), $existingVersions)) {
                $this->logger->debug('Removing version because packagist does not know it anymore: '.$versionFromDB->getId());

                $this->versions->remove($versionFromDB);
            }
        }
    }

    private function updateVersionForPackageFromInfo(Package $package, VersionInfo $versionInfo)
    {
        $version = $this->versions->getByPackageAndVersion($package, $versionInfo->getVersion());

        if($version) {
            // Need to check if the source_ref has changed
            if($version->getSourceReference() != $versionInfo->getSource()->getReference()) {
                $this->logger->info('Newer reference found for '.$package->getName() .'@'.$versionInfo->getVersion());
                $this->versions->remove($version);
                $version = false;
            }
        }

        if(!$version) {

            if(!$versionInfo->getSource()) {
                $this->logger->info('PackageVersion has no source '.$package->getName() .'@'.$versionInfo->getVersion());

                return;
            }

            // If there is no PackageVersion, there cant be a TravisContent
            // We need to fetch the travis content for that revision to make sure
            // that the needed TravisContent exists.

            $travisContent = $this->fetchTravisContent($package, $versionInfo);

            $this->logger->debug('Creating new PackageVersion for '.$package->getName() .'@'.$versionInfo->getVersion());

            $version = new PackageVersion($package, $travisContent);
            $version->setSourceReference($versionInfo->getSource()->getReference());
            $version->setVersion($versionInfo->getVersion());
            $version->setVersionNormalized($versionInfo->getVersionNormalized());
            $version->setVersion($versionInfo->getVersion());

            $this->versions->save($version);
        }
    }

    private function fetchTravisContent(Package $package, VersionInfo $versionInfo) : TravisContent
    {
        $content = $this->travisFetcher->fetchTravisContentFromSource($versionInfo->getSource());

        // Using the source reference instead.
        if(false !== $content) {
            $ref = $content['ref'];
        }else{
            $ref = $versionInfo->getSource()->getReference();
        }

        $travisContent = $this->travisContents->getByPackageAndSourceReference($package, $ref);

        if(!$travisContent) {
            // Create missing content.
            $travisContent = new TravisContent($package);
            if(false === $content) {
                $travisContent->setFileExists(false);
            }else{
                $travisContent->setFileExists(true);
                $travisContent->setContent($content['content']);
                $travisContent->setSourceReference($ref);
                $travisContent->setHhvmStatus($this->travisParser->getHHVMStatus($content['content']));
            }
        }

        $this->travisContents->save($travisContent);

        return $travisContent;
    }

    private function ensurePackageExists(string $name, PackageInfo $packageInfo) : Package {
        // Get package entity from database, or create a new entity with infos from packageist.
        $package = $this->packages->getByName($name);

        if(!$package) {
            // Create a new package
            $package = new Package();
        }

        // Always updating package info.
        $package->setName($packageInfo->getName());
        $package->setDescription($packageInfo->getDescription());
        $package->setTime(new \DateTime($packageInfo->getTime()));
        $package->setType($packageInfo->getType());

        $this->packages->save($package);

        return $package;
    }
}
