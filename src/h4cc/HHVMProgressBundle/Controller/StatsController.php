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
        $query->select("DATE_FORMAT(v.time, '%Y-%m') AS time_grouped, v.hhvm_status, count(v.hhvm_status) as hhvm_status_count");
        $query->where($query->expr()->gt('YEAR(v.time)', "'2000'"));
        $query->groupBy('time_grouped');
        $query->addGroupBy('v.hhvm_status');

        $result = $query->getQuery()->getResult();

        // group data
        $series = array();
        $dates = array();
        foreach($result as $r) {
            $dates[] = $r['time_grouped'];
            $series[$r['hhvm_status']][$r['time_grouped']] = $r['hhvm_status_count'];
        }

        $dates = array_unique($dates);
        sort($dates);

        // Ensure not emtpy time frames.
        foreach($dates as $date) {
            foreach($series as $status => $serie) {
                if(!isset($series[$status][$date])) {
                    $series[$status][$date] = 0;
                }
            }
        }

        // TODO: Cache the query result.

        $data = array(
            array(
                "name" => "Not tested",
                "data" => array_values($series[PackageVersion::HHVM_STATUS_NONE]),
                'color' => '#D9534F',
            ),
            array(
                "name" => "Partially tested",
                "data" => array_values($series[PackageVersion::HHVM_STATUS_ALLOWED_FAILURE]),
                'color' => '#F0AD4E',
            ),
            array(
                "name" => "Tested",
                "data" => array_values($series[PackageVersion::HHVM_STATUS_SUPPORTED]),
                'color' => '#5CB85C',
            )
        );

        $chart = new Highchart();
        $chart->chart->renderTo('statistics_chart'); // The #id of the div where to render the chart
        $chart->title->text('HHVM Support');
        $chart->chart->type('area');
        $chart->xAxis->categories($dates);
        $chart->xAxis->labels(array('rotation' => -90));
        $chart->yAxis->title(array('text' => "Number of Packages"));
        $chart->plotOptions->area(array('stacking' => 'normal'));
        $chart->series($data);
        // Dont forget the credits :)
        $chart->credits->text('by @h4cc');
        $chart->credits->href('http://hhvm.h4cc.de/');

        return $this->render('h4ccHHVMProgressBundle:Stats:index.html.twig', array('chart' => $chart));
    }
}
