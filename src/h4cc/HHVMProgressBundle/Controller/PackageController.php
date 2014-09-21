<?php

namespace h4cc\HHVMProgressBundle\Controller;

use h4cc\HHVMProgressBundle\Entity\PackageVersion;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PackageController extends Controller
{
    const PER_PAGE = 100;

    public function listSupportingAction(Request $request)
    {
        $packages = $this->get('h4cc_hhvm_progress.repos.package_version')->getAllByMaxHHVMStatus(PackageVersion::HHVM_STATUS_SUPPORTED);

        $pagination = $this->paginate($packages, $request);

        return $this->render('h4ccHHVMProgressBundle:Package:list_supporting.html.twig', array('pagination' => $pagination));
    }

    public function listAllowedFailureAction(Request $request)
    {
        $packages = $this->get('h4cc_hhvm_progress.repos.package_version')->getAllByMaxHHVMStatus(PackageVersion::HHVM_STATUS_ALLOWED_FAILURE);

        $pagination = $this->paginate($packages, $request);

        return $this->render('h4ccHHVMProgressBundle:Package:list_allowed_failure.html.twig', array('pagination' => $pagination));
    }

    public function needingHelpAction(Request $request)
    {




        $repo = $this->get('h4cc_hhvm_progress.repos.package_version');

        $packages = array_merge(
            $repo->getAllByMaxHHVMStatus(PackageVersion::HHVM_STATUS_UNKNOWN),
            $repo->getAllByMaxHHVMStatus(PackageVersion::HHVM_STATUS_NONE)
        );

        $pagination = $this->paginate($packages, $request);

        return $this->render(
                    'h4ccHHVMProgressBundle:Package:needing_help.html.twig',
                    array('pagination' => $pagination)
        );
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
        /** @var PackageVersion[] $versions */
        $versions = $this->get('h4cc_hhvm_progress.repos.package_version')->getByName($name);

        if(!$versions) {
            return new JsonResponse(array('error' => 404), 404);
        }

        // Get Latest version, prefer dev-master
        $latestVersion = reset($versions);
        foreach($versions as $version) {
            if('9999999-dev' == $version->getVersion()) {
                $latestVersion = $version;
                break;
            }
        }

        $response = array(
            'name' => $latestVersion->getName(),
            'description' => $latestVersion->getDescription(),
            'versions' => array(),
        );

        foreach($versions as $version) {
            $response['versions'][$version->getVersion()] = array(
                'version' => $version->getVersion(),
                'reference' => $version->getGitReference(),
                'type' => $version->getType(),
                'hhvm_status_string' => $version->getHhvmStatusAsString(),
                'hhvm_status' => $version->getHhvmStatus(),
            );
        }

        return new JsonResponse($response);
    }

    public function showPackageAction($name)
    {
        $versions = $this->get('h4cc_hhvm_progress.repos.package_version')->getByName($name);

        return $this->render('h4ccHHVMProgressBundle:Package:show_versions.html.twig', array('name' => $name, 'versions' => $versions));
    }

    private function paginate($rows, Request $request) {
        $paginator  = $this->get('knp_paginator');
        return $paginator->paginate($rows, $request->query->get('page', 1), static::PER_PAGE);
    }
}
