<?php

namespace h4cc\HHVMProgressBundle\Controller;

use Composer\Package\Version\VersionParser;
use h4cc\HHVMProgressBundle\Entity\PackageVersion;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Serializer;

class ComposerCheckController extends Controller
{
    public function formAction()
    {
        return $this->render('h4ccHHVMProgressBundle:ComposerCheck:form.html.twig');
    }

    /*
     * THIS needs to get refactored into a service.
     */
    public function checkAction(Request $request)
    {
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $composerLock */
        $composerLock = $request->files->get('composer_lock');
        $composerLockContent = file_get_contents($composerLock->getPathname());

        $packages = $this->getPackagesAndVersionsFromComposerLockContent($composerLockContent);

        $versionsRepo = $this->get('h4cc_hhvm_progress.repos.package_version');

        $checkedPackages = array_map(function(array $package) use($versionsRepo) {
            /** @var PackageVersion $version */
            $version = $versionsRepo->get($package['name'], $package['version']);
            if(!$version) {
                $package['hhvm_status'] = PackageVersion::HHVM_STATUS_UNKNOWN;
            }else{
                $package['hhvm_status'] = $version->getHhvmStatus();
            }
            return $package;
        }, $packages);

        return $this->render('h4ccHHVMProgressBundle:ComposerCheck:check.html.twig', array('result' => $checkedPackages));
    }

    protected function getPackagesAndVersionsFromComposerLockContent($content) {
        $data = $this->decodeComposerLock($content);
        if(!$data) {
            return false;
        }
        return $this->getPackagesAndVersions($data);
    }

    protected function getPackagesAndVersions(array $data) {
        $versionParser = new VersionParser();

        $packages = array();

        foreach($data['packages'] as $package) {
            $packages[$package['name']] = array(
                'name' => $package['name'],
                'version' => $versionParser->normalize($package['version']),
                'description' => $package['description'],
                'dev' => false,
            );
        }

        foreach($data['packages-dev'] as $package) {
            $packages[$package['name']] = array(
              'name' => $package['name'],
              'version' => $versionParser->normalize($package['version']),
              'description' => $package['description'],
              'dev' => true,
            );
        }

        return $packages;
    }

    protected function decodeComposerLock($content) {
        $serializer = new Serializer(array(), array(new JsonDecode(true)));
        return $serializer->decode($content, 'json');
    }
}
