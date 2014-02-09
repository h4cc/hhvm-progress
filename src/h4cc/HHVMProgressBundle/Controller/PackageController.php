<?php

namespace h4cc\HHVMProgressBundle\Controller;

use h4cc\HHVMProgressBundle\Entity\PackageVersion;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PackageController extends Controller
{
    public function listSupportingAction()
    {
        $packages = $this->get('h4cc_hhvm_progress.repos.package_version')->getAllByHHVMStatus(PackageVersion::HHVM_STATUS_SUPPORTED);

        return $this->render('h4ccHHVMProgressBundle:Package:list_supporting.html.twig', array('packages' => $packages));
    }

    public function listAllowedFailureAction()
    {
        $packages = $this->get('h4cc_hhvm_progress.repos.package_version')->getAllByHHVMStatus(PackageVersion::HHVM_STATUS_ALLOWED_FAILURE);

        return $this->render('h4ccHHVMProgressBundle:Package:list_allowed_failure.html.twig', array('packages' => $packages));
    }

    public function showPackageAction($name)
    {
        $versions = $this->get('h4cc_hhvm_progress.repos.package_version')->getByName($name);

        return $this->render('h4ccHHVMProgressBundle:Package:show_versions.html.twig', array('name' => $name, 'versions' => $versions));
    }
}
