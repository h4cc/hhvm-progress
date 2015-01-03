<?php

namespace h4cc\HHVMProgressBundle\Controller;

use Composer\Package\Version\VersionParser;
use h4cc\HHVMProgressBundle\Entity\PackageVersion;
use h4cc\HHVMProgressBundle\Graph\GraphComposer;
use h4cc\HHVMProgressBundle\HHVM;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Serializer;

class ComposerCheckController extends Controller
{
    public function formAction()
    {
        return $this->render('h4ccHHVMProgressBundle:ComposerCheck:form.html.twig');
    }

    public function graphAction(Request $request)
    {
        $composerContent = $this->get('session')->get('composer.content');
        $composerLockContent = $this->get('session')->get('composer_lock.content');

        if (!$composerContent || !$composerLockContent) {
            return $this->render('h4ccHHVMProgressBundle:ComposerCheck:error_check_first.html.twig');
        }

        $includeDevs = (1 == $request->get('dev'));

        $graph = $this->get('h4cc_hhvm_progress.graph_composer');
        $graph->analyze($composerContent, $composerLockContent, $includeDevs);

        $graphImage = $graph->getImage();

        return new Response($graphImage, 200, array('Content-Type' => 'image/png'));
    }

    public function checkAction(Request $request)
    {
        if (!$request->files->has('composer_lock')) {
            return $this->formAction();
        }

        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $composerLock */
        $composerLock = $request->files->get('composer_lock');
        $composerLockContent = file_get_contents($composerLock->getPathname());
        $this->get('session')->set('composer_lock.content', $composerLockContent);


        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $composerLock */
        $composer = $request->files->get('composer');
        if ($composer) {
            $composerContent = file_get_contents($composer->getPathname());
            $this->get('session')->set('composer.content', $composerContent);
        } else {
            $this->get('session')->remove('composer.content');
        }

        $packages = $this->getPackagesAndVersionsFromComposerLockContent($composerLockContent);

        /** @var \h4cc\HHVMProgressBundle\Entity\PackageVersionRepository $versionsRepo */
        $travisRepo = $this->get('h4cc_hhvm_progress.repos.travis_content');
        $versionsRepo = $this->get('h4cc_hhvm_progress.repos.package_version');

        $hhvmMaxStatus = $travisRepo->getMaxHHVMStatusForNames();

        $checkedPackages = array_map(function (array $package) use ($versionsRepo, $hhvmMaxStatus) {

            $packageName = $package['name'];
            $package['hint'] = '';
            if (0 === stripos($packageName, 'symfony/')) {
                $packageName = 'symfony/symfony';
                $package['hint'] = 'Used HHVM status from symfony/symfony instead.';
            }

            $version = $versionsRepo->getByPackageNameAndVersion($packageName, $package['version']);

            if (!$version) {
                $package['hhvm_status'] = HHVM::STATUS_UNKNOWN;
            } else {
                $package['hhvm_status'] = $version->getTravisContent()->getHhvmStatus();
            }
            $package['hhvm_status_max'] = $package['hhvm_status'];
            if (isset($hhvmMaxStatus[$packageName])) {
                $package['hhvm_status_max'] = $hhvmMaxStatus[$packageName];
            }
            return $package;
        }, $packages);

        return $this->render(
            'h4ccHHVMProgressBundle:ComposerCheck:check.html.twig',
            array(
                'result' => $checkedPackages,
                'show_graph' => $this->get('session')->has('composer.content')
            )
        );
    }

    protected function getPackagesAndVersionsFromComposerLockContent($content)
    {
        $data = $this->decodeComposerLock($content);
        if (!$data) {
            return false;
        }
        return $this->getPackagesAndVersions($data);
    }

    protected function getPackagesAndVersions(array $data)
    {
        $versionParser = new VersionParser();

        $packages = array();

        foreach ($data['packages'] as $package) {
            $packages[$package['name']] = array(
                'name' => $package['name'],
                'type' => isset($package['type']) ? $package['type'] : '',
                'version' => $versionParser->normalize($package['version']),
                'description' => isset($package['description']) ? $package['description'] : '',
                'dev' => false,
            );
        }

        foreach ($data['packages-dev'] as $package) {
            $packages[$package['name']] = array(
                'name' => $package['name'],
                'type' => isset($package['type']) ? $package['type'] : '',
                'version' => $versionParser->normalize($package['version']),
                'description' => isset($package['description']) ? $package['description'] : '',
                'dev' => true,
            );
        }

        return $packages;
    }

    protected function decodeComposerLock($content)
    {
        $serializer = new Serializer(array(), array(new JsonDecode(true)));
        return $serializer->decode($content, 'json');
    }
}
