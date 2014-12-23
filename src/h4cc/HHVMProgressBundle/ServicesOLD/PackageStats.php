<?php


namespace h4cc\HHVMProgressBundle\Services;


use h4cc\HHVMProgressBundle\Entity\PackageStatsRepository;
use h4cc\HHVMProgressBundle\Entity\PackageVersion;
use h4cc\HHVMProgressBundle\Entity\PackageVersionRepository;

class PackageStats
{
    /**
     * @var \h4cc\HHVMProgressBundle\Entity\PackageVersionRepository
     */
    private $packages;

    /**
     * @var \h4cc\HHVMProgressBundle\Entity\PackageStatsRepository
     */
    private $stats;

    public function __construct(PackageVersionRepository $packages, PackageStatsRepository $stats) {
        $this->packages = $packages;
        $this->stats = $stats;
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

    public function fetchStatsFromDate(\DateTime $startDate)
    {
        // We start with a map of packages => lowest hhvm status.
        $packages = array_fill_keys($this->packages->getAllPackageNames(), PackageVersion::HHVM_STATUS_UNKNOWN);

        // Then we ask for each day from $startDate till now, which packages changed to which max(hhvm_status) on that day.
        $days = $this->listDaysSince($startDate);
        foreach($days as $date) {
            $packagesStatus = $this->packages->getMaxHHVMStatusOnDay($date);

            foreach($packagesStatus as $packageStatus) {
                // One Package with its max hhvm_status from one day.
                $packages[$packageStatus->getName()] = $packageStatus->getHhvmStatus();
            }

            // Filter $packages map for numbers
            $stats = array_count_values($packages);

            // Store these numbers.
            $this->stats->saveStats(new \DateTime($date), $stats);
        }
    }

    private function listDaysSince(\DateTime $start)
    {
        $date = clone $start;
        $now = new \DateTime("today");

        $dates = array();

        while ($date < $now) {
            $dates[] = $date->format('Y-m-d');
            $date->add(new \DateInterval('P1D'));
        }
        $dates[] = $date->format('Y-m-d');

        return $dates;
    }

}
 