<?php

namespace h4cc\HHVMProgressBundle\Controller;

use Composer\Package\Version\VersionParser;
use h4cc\HHVMProgressBundle\Entity\PackageVersion;
use h4cc\HHVMProgressBundle\HHVM;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BadgesController extends Controller
{
    public function jsonAction($name, Request $request)
    {
        $branch = $request->get('branch', 'dev-master');
        $packageVersion = $this->getPackageVersion($name, $branch);

        $hhvmStatusString = HHVM::getStringForStatus($packageVersion->getTravisContent()->getHhvmStatus());

        $response = new JsonResponse(array('hhvm_status' => $hhvmStatusString));
        $response->headers->set('Cache-Control', sprintf('public, maxage=%s, s-maxage=%s', 3600, 3600));

        return $response;
    }

    public function showAction($name, Request $request, $type)
    {
        $branch = $request->get('branch', 'dev-master');

        $packageVersion = $this->getPackageVersion($name, $branch);

        $badgeFile = HHVM::getStringForStatus($packageVersion->getTravisContent()->getHhvmStatus());

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
        $package = $repo = $this->get('h4cc_hhvm_progress.repos.package')->getByName($name);

        if(!$package) {
            throw new NotFoundHttpException();
        }

        $repo = $this->get('h4cc_hhvm_progress.repos.package_version');
        $versionParser = new VersionParser();

        $version = $repo->getByPackageAndVersion($package, $versionParser->normalize($branch));
        if($version) {
            return $version;
        }

        $version = $repo->getByPackageAndVersion($package, $branch);
        if(!$version) {
            throw new NotFoundHttpException();
        }

        return $version;
    }
}
