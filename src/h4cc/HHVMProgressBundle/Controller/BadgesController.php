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
        $packageVersion = $this->getPackageVersion($name, $request->get('branch', 'dev-master'));

        if(!$packageVersion) {
            throw new NotFoundHttpException();
        }

        $hhvmStatusString = HHVM::getStringForStatus($packageVersion->getTravisContent()->getHhvmStatus());

        $response = new JsonResponse(array('hhvm_status' => $hhvmStatusString));
        $response->headers->set('Cache-Control', sprintf('public, maxage=%s, s-maxage=%s', 3600, 3600));

        return $response;
    }

    public function showAction($name, Request $request, $type)
    {
        $packageVersion = $this->getPackageVersion($name, $request->get('branch', 'dev-master'));

        if(!$packageVersion) {
            return new Response('', 404);
        }

        $badgeFile = HHVM::getStringForStatus($packageVersion->getTravisContent()->getHhvmStatus());

        $badgeStyle = $this->getBadgeStyleFromRequest($request);

        if('svg' == $type) {
            $response = new Response(file_get_contents(__DIR__.'/../Resources/badges/'.$badgeStyle.'/hhvm_'.$badgeFile.'.svg'));
            $response->headers->set('Content-Type', 'image/svg+xml;charset=utf-8');
        }else{
            $response = new Response(file_get_contents(__DIR__.'/../Resources/badges/'.$badgeStyle.'/hhvm_'.$badgeFile.'.png'));
            $response->headers->set('Content-Type', 'image/png');
        }

        $response->headers->set('Cache-Control', sprintf('public, maxage=%s, s-maxage=%s', 3600, 3600));

        return $response;
    }

    private function getBadgeStyleFromRequest(Request $request)
    {
        switch(strtolower($request->get('style'))) {
            case 'flat':
                return 'flat';
            case 'flat-square':
                return 'flat-square';
        }

        return 'plastic';
    }

    private function getPackageVersion($name, $branchParameter)
    {
        foreach(array($branchParameter, 'dev-'.$branchParameter) as $branch) {
            $packageVersion = $this->realGetPackageVersion($name, $branch);
            if($packageVersion) {
                return $packageVersion;
            }
        }
        return null;
    }

    private function realGetPackageVersion($name, $branch)
    {
        $package = $repo = $this->get('h4cc_hhvm_progress.repos.package')->getByName($name);
        if(!$package) {
            return null;
        }

        $repo = $this->get('h4cc_hhvm_progress.repos.package_version');
        $versionParser = new VersionParser();

        $version = $repo->getByPackageAndVersion($package, $versionParser->normalize($branch));
        if($version) {
            return $version;
        }

        $version = $repo->getByPackageAndVersion($package, $branch);
        if(!$version) {
            return null;
        }

        return $version;
    }
}
