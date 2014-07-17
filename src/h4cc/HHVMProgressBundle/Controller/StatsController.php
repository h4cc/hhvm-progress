<?php

namespace h4cc\HHVMProgressBundle\Controller;

use h4cc\HHVMProgressBundle\Entity\PackageVersion;
use Ob\HighchartsBundle\Highcharts\Highchart;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class StatsController extends Controller
{
    public function indexAction()
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
        // TODO: Cache the query result.

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

        $chart = new Highchart();

        $chart->chart->renderTo('statistics_chart'); // The #id of the div where to render the chart
        $chart->title->text('HHVM Support');
        $chart->chart->type('area');

        $chart->xAxis->labels(array('rotation' => -60));
        $chart->yAxis->title(array('text' => "Number of tested Releases"));
        $chart->plotOptions->area(array('stacking' => 'normal'));

        $chart->xAxis->categories($yearsAndMonths);
        $chart->series($series);

        // Dont forget the credits :)
        $chart->credits->text('by @h4cc');
        $chart->credits->href('http://hhvm.h4cc.de/');

        return $this->render('h4ccHHVMProgressBundle:Stats:index.html.twig', array('chart' => $chart));
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
}
