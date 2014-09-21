<?php

namespace h4cc\HHVMProgressBundle\Controller;

use h4cc\HHVMProgressBundle\Entity\PackageStats;
use h4cc\HHVMProgressBundle\Entity\PackageStatsRepository;
use h4cc\HHVMProgressBundle\Entity\PackageVersion;
use Ob\HighchartsBundle\Highcharts\Highchart;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class StatsController extends Controller
{
    public function indexAction()
    {
        $hhvmToYearsAndMonths = $this->mapFromHHVMStatusToYearsAndMonths();
        $packages = $this->fetchPackageVersionGroupedByTimeAndHHVMStatus();
        $yearsAndMonths = $this->listYearsAndMonthsSince('2013', '02');

        /** @var PackageStatsRepository $repo */
        $repo = $this->get('h4cc_hhvm_progress.repos.package_stats');
        /** @var PackageStats[] $stats */
        $packageStats = $repo->fetchAll();

        return $this->render(
            'h4ccHHVMProgressBundle:Stats:index.html.twig',
            array(
                'chart_packages' => $this->getChartPackages($hhvmToYearsAndMonths, $packages, $yearsAndMonths),
                'pie_chart_packages' => $this->getPieChartPackages($packages),

                'chart_releases' => $this->getChartReleases($packageStats),
                'pie_chart_releases' => $this->getPieChartReleases($packageStats),
            )
        );
    }

    private function getPieChartReleases($packageStats)
    {
        $stats = $packageStats;
        $sums = array();

        foreach($stats as $stat) {
            foreach($this->getStatsForAllHHVMStatus($stat) as $hhvmStatus => $count) {
                if(!isset($sums[$hhvmStatus])) {
                    $sums[$hhvmStatus] = 0;
                }
                $sums[$hhvmStatus] += $count;
            }
        }

        $chart = $this->newChart('statistics_pie_chart_releases');

        $chart->title->text('HHVM Support - Total Releases');
        $chart->tooltip->pointFormat('Amount: <b>{point.y}</b><br/>Percent: <b>{point.percentage:.1f}%</b>');

        $chart->plotOptions->pie(array(
            'allowPointSelect' => true,
            'cursor' => 'pointer',
            'dataLabels' => array(
                'enabled' => false,
            ),
            'showInLegend' => true,
        ));

        $series = array(array(
            'type' => 'pie',
            'name' => 'Releases',
            'data' => array(
                array(
                    'name' => 'Tested',
                    'color' => '#5CB85C',
                    'y' => $sums[PackageVersion::HHVM_STATUS_SUPPORTED],
                ),
                array(
                    'name' => 'Partially tested',
                    'color' => '#F0AD4E',
                    'y' => $sums[PackageVersion::HHVM_STATUS_ALLOWED_FAILURE],
                ),
                array(
                    'name' => 'Not tested',
                    'color' => '#D9534F',
                    'y' => $sums[PackageVersion::HHVM_STATUS_NONE],
                ),
            ),)
        );

        $chart->series($series);

        return $chart;
    }

    private function getChartReleases($packageStats)
    {
        $stats = $packageStats;

        $data = array();
        $dates = array();
        foreach($stats as $stat) {
            $date = $stat->getDate()->format('Y-m');
            $dates[] = $date;

            foreach($this->getStatsForAllHHVMStatus($stat) as $hhvmStatus => $count) {
                $hhvmStatus = (int)$hhvmStatus;
                if(!isset($data[$hhvmStatus][$date])) {
                    $data[$hhvmStatus][$date] = 0;
                }
                $data[$hhvmStatus][$date] += $count;
            }
        }

        $dates = array_values(array_unique($dates));

        // Removing the last month, because it is not yet complete and will show strange numbers.
        array_pop($dates);
        foreach($data as $hhvmStatus => $list) {
            array_pop($list);
            $data[$hhvmStatus] = $list;
        }

        $series = array(
            array(
                "name" => "Tested",
                "data" => array_values($data[PackageVersion::HHVM_STATUS_SUPPORTED]), //$this->sumPrevToCurrentArray($data[PackageVersion::HHVM_STATUS_SUPPORTED]),
                'color' => '#5CB85C',
            ),
            array(
                "name" => "Partially tested",
                "data" => array_values($data[PackageVersion::HHVM_STATUS_ALLOWED_FAILURE]), //$this->sumPrevToCurrentArray($data[PackageVersion::HHVM_STATUS_ALLOWED_FAILURE]),
                'color' => '#F0AD4E',
            ),
            array(
                "name" => "Not tested",
                "data" => array_values($data[PackageVersion::HHVM_STATUS_NONE]), //$this->sumPrevToCurrentArray($data[PackageVersion::HHVM_STATUS_NONE]),
                'color' => '#D9534F',
            ),
        );

        $chart = $this->newChart('statistics_chart_releases');

        $chart->title->text('HHVM Support - Releases');
        $chart->chart->type('area');

        $chart->xAxis->labels(array('rotation' => -60));
        $chart->yAxis->title(array('text' => "Number of tested Releases"));
        $chart->plotOptions->area(array('stacking' => 'normal'));

        $chart->xAxis->categories($dates);
        $chart->series($series);

        // Modify the tooltip
        $chart->tooltip->shared(true);

        return $chart;
    }

    private function getPieChartPackages($packages)
    {
        $sums = array_combine(PackageVersion::getAllHHVMStatus(), array_fill(0, count(PackageVersion::getAllHHVMStatus()), 0));

        foreach($packages as $package) {
            $sums[$package['hhvm_status']] += $package['hhvm_status_count'];
        }

        $chart = $this->newChart('statistics_pie_chart_packages');
        $chart->title->text('HHVM Support - Total Packages');
        $chart->tooltip->pointFormat('Amount: <b>{point.y}</b><br/>Percent: <b>{point.percentage:.1f}%</b>');

        $chart->plotOptions->pie(array(
            'allowPointSelect' => true,
            'cursor' => 'pointer',
            'dataLabels' => array(
                'enabled' => false,
            ),
            'showInLegend' => true,
        ));

        $series = array(array(
            'type' => 'pie',
            'name' => 'Packages',
            'data' => array(
                array(
                    'name' => 'Tested',
                    'color' => '#5CB85C',
                    'y' => $sums[PackageVersion::HHVM_STATUS_SUPPORTED],
                ),
                array(
                    'name' => 'Partially tested',
                    'color' => '#F0AD4E',
                    'y' => $sums[PackageVersion::HHVM_STATUS_ALLOWED_FAILURE],
                ),
                array(
                    'name' => 'Not tested',
                    'color' => '#D9534F',
                    'y' => $sums[PackageVersion::HHVM_STATUS_NONE],
                ),
            ),)
        );

        $chart->series($series);

        return $chart;
    }

    private function getChartPackages($hhvmStatusToYearAndMonths, $packages, $yearsAndMonths)
    {
        $data = $hhvmStatusToYearAndMonths;
        $result = $packages;

        foreach ($result as $row) {
            if (isset($data[$row['hhvm_status']][$row['time']])) {
                $data[$row['hhvm_status']][$row['time']] = (int)$row['hhvm_status_count'];
            }
        }

        $series = array(
            array(
                "name" => "Tested",
                "data" => $this->sumPrevToCurrentArray($data[PackageVersion::HHVM_STATUS_SUPPORTED]),
                'color' => '#5CB85C',
            ),
            array(
                "name" => "Partially tested",
                "data" => $this->sumPrevToCurrentArray($data[PackageVersion::HHVM_STATUS_ALLOWED_FAILURE]),
                'color' => '#F0AD4E',
            ),
            array(
                "name" => "Not tested",
                "data" => $this->sumPrevToCurrentArray($data[PackageVersion::HHVM_STATUS_NONE]),
                'color' => '#D9534F',
            ),
        );

        $chart = $this->newChart('statistics_chart_packages');
        $chart->title->text('HHVM Support - Packages');
        $chart->chart->type('area');

        $chart->xAxis->labels(array('rotation' => -60));
        $chart->yAxis->title(array('text' => "Number of tested Packages"));
        $chart->plotOptions->area(array('stacking' => 'normal'));

        $chart->xAxis->categories($yearsAndMonths);
        $chart->series($series);

        // Modify the tooltip
        $chart->tooltip->shared(true);

        return $chart;
    }

    private function sumPrevToCurrentArray(array $numbers)
    {
        $summed = array();
        $sum = 0;
        foreach ($numbers as $number) {
            $summed[] = $number + $sum;
            $sum += $number;
        }

        return $summed;
    }

    private function listYearsAndMonthsSince($year, $month)
    {
        $date = new \DateTime("$year-$month-2");
        $now = new \DateTime("first day of this month");

        $dates = array();

        while ($date < $now) {
            $dates[] = $date->format('Y-m');
            $date->add(new \DateInterval('P1M'));
        }
        $dates[] = $date->format('Y-m');

        return $dates;
    }

    private function getStatsForAllHHVMStatus(PackageStats $stat)
    {
        $stats = $stat->getStats();

        foreach(PackageVersion::getAllHHVMStatus() as $status) {
            if(!isset($stats[$status])) {
                $stats[$status] = 0;
            }
        }

        return $stats;
    }

    private function mapFromHHVMStatusToYearsAndMonths()
    {
        // Transforming data
        $yearsAndMonths = $this->listYearsAndMonthsSince('2013', '02');
        $data = array(
            PackageVersion::HHVM_STATUS_NONE => array(),
            PackageVersion::HHVM_STATUS_ALLOWED_FAILURE => array(),
            PackageVersion::HHVM_STATUS_SUPPORTED => array(),
        );
        foreach ($yearsAndMonths as $date) {
            foreach ($data as $key => $value) {
                $data[$key][$date] = 0;
            }
        }

        return $data;
    }

    private function fetchPackageVersionGroupedByTimeAndHHVMStatus()
    {
        // Fetching data by hand.

        /** @var \Doctrine\ORM\EntityRepository $repo */
        $repo = $this->get('h4cc_hhvm_progress.doctrine_repo.package_version');
        $query = $repo->createQueryBuilder('v');
        $query->select(
            "DATE_FORMAT(v.time, '%Y-%m') AS time, v.hhvm_status, count(v.hhvm_status) as hhvm_status_count"
        );
        $query->where($query->expr()->gt('YEAR(v.time)', "'2000'"));
        $query->andWhere('v.hhvm_status IN (:status)');
        $query->groupBy('time');
        $query->addGroupBy('v.hhvm_status');
        $query->orderBy('time');

        $query->setParameter(
            ':status',
            array(
                PackageVersion::HHVM_STATUS_NONE,
                PackageVersion::HHVM_STATUS_ALLOWED_FAILURE,
                PackageVersion::HHVM_STATUS_SUPPORTED,
            )
        );

        $result = $query->getQuery()->getResult();

        return $result;
    }

    private function newChart($divId)
    {
        $chart = new Highchart();

        $chart->chart->renderTo($divId);

        // Dont forget the credits :)
        $chart->credits->text('by @h4cc');
        $chart->credits->href('http://hhvm.h4cc.de/');

        return $chart;
    }
}
