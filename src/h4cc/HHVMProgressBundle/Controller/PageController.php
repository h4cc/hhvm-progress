<?php

namespace h4cc\HHVMProgressBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PageController extends Controller
{
    public function indexAction()
    {
        $stats = $this->get('h4cc_hhvm_progress.package.stats')->getStatsByHHVMState();

        // Calculate percentages of progress bars.
        $stats['supported_percent'] = max(5, $stats['supported'] / $stats['total'] * 100);
        $stats['allowed_failure_percent'] = max(5, $stats['allowed_failure'] / $stats['total'] * 100);
        $stats['not_supported_percent'] = 100 - $stats['supported_percent'] - $stats['allowed_failure_percent'];

        return $this->render('h4ccHHVMProgressBundle:Page:index.html.twig', array('stats' => $stats));
    }
}
