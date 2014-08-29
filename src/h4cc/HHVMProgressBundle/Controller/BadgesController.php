<?php

namespace h4cc\HHVMProgressBundle\Controller;

use Composer\Package\Version\VersionParser;
use h4cc\HHVMProgressBundle\Entity\PackageVersion;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BadgesController extends Controller
{
    public function jsonAction($name, Request $request)
    {
        $branch = $request->get('branch', 'dev-master');

        $packageVersion = $this->getPackageVersion($name, $branch);

        $hhvmStatusString = 'unknown';
        if($packageVersion) {
            switch($packageVersion->getHhvmStatus()) {
                case PackageVersion::HHVM_STATUS_SUPPORTED:
                    $hhvmStatusString = 'tested';
                    break;
                case PackageVersion::HHVM_STATUS_NONE:
                    $hhvmStatusString = 'not_tested';
                    break;
                case PackageVersion::HHVM_STATUS_ALLOWED_FAILURE:
                    $hhvmStatusString = 'partial';
                    break;
            }
        }

        $response = new JsonResponse(array('hhvm_status' => $hhvmStatusString));
        $response->headers->set('Cache-Control', sprintf('public, maxage=%s, s-maxage=%s', 3600, 3600));

        return $response;
    }

    public function showAction($name, Request $request, $type)
    {
        $branch = $request->get('branch', 'dev-master');

        $packageVersion = $this->getPackageVersion($name, $branch);

        $badgeFile = 'unknown';
        if($packageVersion) {
            switch($packageVersion->getHhvmStatus()) {
                case PackageVersion::HHVM_STATUS_SUPPORTED:
                    $badgeFile = 'tested';
                    break;
                case PackageVersion::HHVM_STATUS_NONE:
                    $badgeFile = 'not_tested';
                    break;
                case PackageVersion::HHVM_STATUS_ALLOWED_FAILURE:
                    $badgeFile = 'partial';
                    break;
            }
        }

        if('svg' == $type) {
            $response = new Response(file_get_contents(__DIR__.'/../Resources/badges/hhvm_'.$badgeFile.'.svg'));
            $response->headers->set('Content-Type', 'image/svg+xml;charset=utf-8');
        }else{
            $response = new Response(file_get_contents(__DIR__.'/../Resources/badges/hhvm_'.$badgeFile.'.png'));
            $response->headers->set('Content-Type', 'image/png');
        }

        $response->headers->set('Cache-Control', sprintf('public, maxage=%s, s-maxage=%s', 3600, 3600));

        return $response;
    }

    private function getPackageVersion($name, $branch)
    {
        /** @var \h4cc\HHVMProgressBundle\Entity\PackageVersionRepository $repo */
        $repo = $this->get('h4cc_hhvm_progress.repos.package_version');

        // Move this instance to a service.
        $versionParser = new VersionParser();

        // Move this logic also to a service?
        return $repo->get($name, $versionParser->normalize($branch));
    }
}
