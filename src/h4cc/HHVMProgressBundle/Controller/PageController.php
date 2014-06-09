<?php

namespace h4cc\HHVMProgressBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class PageController extends Controller
{
    public function indexAction()
    {
        $stats = $this->getCachedStats();

        if(0 == $stats['total']) {
            // Avoid division by zero errors.
            $stats['total'] = 1;
        }

        // Calculate percentages of progress bars.
        $stats['supported_percent'] = max(5, $stats['supported'] / $stats['total'] * 100);
        $stats['allowed_failure_percent'] = max(5, $stats['allowed_failure'] / $stats['total'] * 100);
        $stats['not_supported_percent'] = 100 - $stats['supported_percent'] - $stats['allowed_failure_percent'];

        return $this->render('h4ccHHVMProgressBundle:Page:index.html.twig', array('stats' => $stats));
    }

    public function scriptDownloadAction()
    {
        $scriptContent = file_get_contents(__DIR__ . '/../Resources/scripts/hhvm_status.php');

        $response = new Response($scriptContent);

        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', 'attachment; filename="hhvm_status.php"');

        return $response;
    }

    private function getCachedStats() {
        $cache = $this->get('memcache.default');

        $stats = $cache->get('stats');

        if(!$stats) {
            $stats = $this->get('h4cc_hhvm_progress.package.stats')->getStatsByHHVMState();

            $cache->set('stats', $stats, 60);
        }

        return $stats;
    }
}
