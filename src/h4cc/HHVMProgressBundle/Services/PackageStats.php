<?php


namespace h4cc\HHVMProgressBundle\Services;


use h4cc\HHVMProgressBundle\Entity\PackageVersion;
use h4cc\HHVMProgressBundle\Entity\PackageVersionRepository;

class PackageStats
{
    /**
     * @var \h4cc\HHVMProgressBundle\Entity\PackageVersionRepository
     */
    private $packages;

    public function __construct(PackageVersionRepository $packages) {
        $this->packages = $packages;
    }

    public function getStatsByHHVMState() {
        // This would be a good point for caching maybe?
        $mapNameStatus = $this->packages->getMaxHHVMStatusForNames();

        $stats = array(
          'total' => count($mapNameStatus),
          'not_supported' => 0,
          'allowed_failure' => 0,
          'supported' => 0,
        );

        foreach($mapNameStatus as $status) {
            switch($status) {
                case PackageVersion::HHVM_STATUS_NONE:
                    ++$stats['not_supported'];
                    break;
                case PackageVersion::HHVM_STATUS_ALLOWED_FAILURE:
                    ++$stats['allowed_failure'];
                    break;
                case PackageVersion::HHVM_STATUS_SUPPORTED:
                    ++$stats['supported'];
                    break;
                case PackageVersion::HHVM_STATUS_NO_PHP:
                case PackageVersion::HHVM_STATUS_UNKNOWN:
                    // Skip these for statistics.
                    break;
                default:
                    throw new \InvalidArgumentException("unknown hhvm_status: $status");
            }
        }

        return $stats;
    }

}
 