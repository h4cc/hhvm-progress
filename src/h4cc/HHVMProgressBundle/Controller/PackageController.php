<?php

namespace h4cc\HHVMProgressBundle\Controller;

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
        $travisContentRepo = $this->get('h4cc_hhvm_progress.repos.travis_content');

        $packagesQuery = $travisContentRepo->getAllByMaxHHVMStatusQuery(HHVM::STATUS_SUPPORTED);

        $counts = $travisContentRepo->getMaxHHVMStatusCountNumeric();
        $packagesQuery->setHint('knp_paginator.count', $counts[HHVM::STATUS_SUPPORTED]);

        $pagination = $this->get('knp_paginator')->paginate($packagesQuery, $request->query->get('page', 1), static::PER_PAGE, array('distinct' => false, 'wrap-queries' => true));

        return $this->render('h4ccHHVMProgressBundle:Package:list_supporting.html.twig', array('pagination' => $pagination));
    }

    public function listAllowedFailureAction(Request $request)
    {
        $travisContentRepo = $this->get('h4cc_hhvm_progress.repos.travis_content');

        $packagesQuery = $travisContentRepo->getAllByMaxHHVMStatusQuery(HHVM::STATUS_ALLOWED_FAILURE);

        $counts = $travisContentRepo->getMaxHHVMStatusCountNumeric();
        $packagesQuery->setHint('knp_paginator.count', $counts[HHVM::STATUS_ALLOWED_FAILURE]);

        $pagination = $this->get('knp_paginator')->paginate($packagesQuery, $request->query->get('page', 1), static::PER_PAGE, array('distinct' => false, 'wrap-queries' => true));

        return $this->render('h4ccHHVMProgressBundle:Package:list_allowed_failure.html.twig', array('pagination' => $pagination));
    }

    public function needingHelpAction(Request $request)
    {
        $travisContentRepo = $this->get('h4cc_hhvm_progress.repos.travis_content');

        $packagesQuery = $travisContentRepo->getAllByMaxHHVMStatusQuery(HHVM::STATUS_NONE);

        $counts = $travisContentRepo->getMaxHHVMStatusCountNumeric();
        $packagesQuery->setHint('knp_paginator.count', $counts[HHVM::STATUS_NONE]);

        $pagination = $this->get('knp_paginator')->paginate($packagesQuery, $request->query->get('page', 1), static::PER_PAGE, array('distinct' => false, 'wrap-queries' => true));

        return $this->render('h4ccHHVMProgressBundle:Package:needing_help.html.twig', array('pagination' => $pagination));
    }

    public function apiUpdatePackageAction($name)
    {
        try {
            $updater = $this->get('h4cc_hhvm_progress.package.updater');
            $updater->updatePackage($name);

            return new JsonResponse(array('result' => 'success'));
        } catch (\Exception $e) {
            return new JsonResponse(array('result' => 'failure'));
        }
    }

    public function apiGetPackageAction($name)
    {
        $package = $this->get('h4cc_hhvm_progress.repos.package')->getByName($name);
        if (!$package) {
            throw new NotFoundHttpException();
        }

        $versions = array();
        foreach ($package->getVersions() as $version) {
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

        $versions = array();
        if ($package) {
            $featureBranches = array();
            foreach ($package->getVersions()->toArray() as $version) {
                if (0 === stripos($version->getVersionNormalized(), 'dev-')) {
                    $featureBranches[$version->getVersionNormalized()] = $version;
                } else {
                    $versions[$version->getVersionNormalized()] = $version;
                }
            }

            uksort($versions, function ($v1, $v2) {
                // Sorting backwars from highest version to lowest.
                return version_compare($v2, $v1);
            });
            $versions = array_merge($featureBranches, $versions);
        }

        return $this->render('h4ccHHVMProgressBundle:Package:show_versions.html.twig', array('name' => $name, 'package' => $package, 'versions' => array_values($versions)));
    }
}
