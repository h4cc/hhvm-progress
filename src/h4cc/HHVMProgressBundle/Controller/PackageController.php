<?php

namespace h4cc\HHVMProgressBundle\Controller;

use h4cc\HHVMProgressBundle\Entity\PackageVersion;
use h4cc\HHVMProgressBundle\HHVM;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PackageController extends Controller
{
    const PER_PAGE = 100;

    public function listSupportingAction(Request $request)
    {
        $packages = $this->get('h4cc_hhvm_progress.repos.travis_content')->getAllByMaxHHVMStatus(HHVM::STATUS_SUPPORTED);

        $pagination = $this->paginate($packages, $request);

        return $this->render('h4ccHHVMProgressBundle:Package:list_supporting.html.twig', array('pagination' => $pagination));
    }

    public function listAllowedFailureAction(Request $request)
    {
        $packages = $this->get('h4cc_hhvm_progress.repos.travis_content')->getAllByMaxHHVMStatus(HHVM::STATUS_ALLOWED_FAILURE);

        $pagination = $this->paginate($packages, $request);

        return $this->render('h4ccHHVMProgressBundle:Package:list_allowed_failure.html.twig', array('pagination' => $pagination));
    }

    public function needingHelpAction(Request $request)
    {
        $packages = $this->get('h4cc_hhvm_progress.repos.travis_content')->getAllByMaxHHVMStatus(HHVM::STATUS_NONE);

        $pagination = $this->paginate($packages, $request);

        return $this->render('h4ccHHVMProgressBundle:Package:needing_help.html.twig', array('pagination' => $pagination));
    }

    public function apiUpdatePackageAction($name)
    {
        try {
            $updater = $this->get('h4cc_hhvm_progress.package.updater');
            $updater->updatePackage($name);

            return new JsonResponse(array('result' => 'success'));
        }catch(\Exception $e) {
            return new JsonResponse(array('result' => 'failure'));
        }
    }

    public function apiGetPackageAction($name)
    {
        $package = $this->get('h4cc_hhvm_progress.repos.package')->getByName($name);
        if(!$package) {
            throw new NotFoundHttpException();
        }

        $versions = array();
        foreach($package->getVersions() as $version) {
            $travis = $version->getTravisContent();
            $versions[$version->getVersionNormalized()] = array(
                'version' => $version->getVersion(),
                'version_normalized' => $version->getVersionNormalized(),
                'reference' => $version->getSourceReference(),
                'type' => $package->getType(),
                'hhvm_status' => $travis->getHhvmStatus(),
                'hhvm_status_string' => $travis->getHhvmStatusString(),
            );
        }

        return new JsonResponse(array(
            'name' => $package->getName(),
            'description' => $package->getDescription(),
            'type' => $package->getType(),
            'versions' => $versions
        ));
    }

    public function showPackageAction($name)
    {
        $package = $this->get('h4cc_hhvm_progress.repos.package')->getByName($name);

        return $this->render('h4ccHHVMProgressBundle:Package:show_versions.html.twig', array('name' => $name, 'package' => $package));
    }

    private function paginate($rows, Request $request) {
        $paginator  = $this->get('knp_paginator');
        return $paginator->paginate($rows, $request->query->get('page', 1), static::PER_PAGE);
    }
}
