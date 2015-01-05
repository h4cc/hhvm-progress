<?php


namespace h4cc\HHVMProgressBundle\Graph;

use Composer\Package\Version\VersionParser;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\GraphViz;
use h4cc\HHVMProgressBundle\Entity\PackageVersion;
use h4cc\HHVMProgressBundle\Entity\PackageVersionRepository;
use h4cc\HHVMProgressBundle\Entity\TravisContentRepository;
use h4cc\HHVMProgressBundle\HHVM;
use JMS\Composer\DependencyAnalyzer;

class GraphComposer
{
    private $layoutVertex = array(
        'fillcolor' => '#eeeeee',
        'style' => 'filled, rounded',
        'shape' => 'box',
        'fontcolor' => '#314B5F'
    );

    private $layoutVertexRoot = array(
        'style' => 'filled, rounded, bold'
    );

    private $layoutEdge = array(
        'fontcolor' => '#767676',
        'fontsize' => 10,
        'color' => '#1A2833'
    );

    private $layoutEdgeDev = array(
        'style' => 'dashed'
    );

    private $dependencyGraph;

    private $format = 'png';

    /** @var  PackageVersionRepository */
    private $repository;

    private $travisRepo;

    private $versionParser;

    private $analyzer;

    private $maxHhvmStatusForName = [];

    public function __construct(PackageVersionRepository $repository, TravisContentRepository $repoTravis)
    {
        $this->repository = $repository;
        $this->travisRepo = $repoTravis;
        $this->versionParser = new VersionParser();

        $this->analyzer = new DependencyAnalyzer();
    }

    public function analyze($composerContent, $composerLockContent, $includeDevs = true)
    {
        $this->maxHhvmStatusForName = $this->travisRepo->getMaxHHVMStatusForNames();
        $this->dependencyGraph = $this->analyzer->analyzeComposerData($composerContent, $composerLockContent, null, $includeDevs);
        return $this;
    }

    private function getLayoutVertexForPackage($name, $version)
    {
        $vertex = $this->layoutVertex;

        if ($version) {
            // Need to normalize version to find it in the database.
            $version = $this->versionParser->normalize($version);
        } else {
            return $vertex;
        }

        if (0 === stripos($name, 'symfony/')) {
            $name = 'symfony/symfony';
        }

        /** @var PackageVersion $packageVersion */
        $packageVersion = $this->repository->getByPackageNameAndVersion($name, $version);

        if (!$packageVersion) {
            return $vertex;
        }

        $hhvmStatus = $packageVersion->getTravisContent()->getHhvmStatus();
        $maxHhvmStatus = isset($this->maxHhvmStatusForName[$name]) ? $this->maxHhvmStatusForName[$name] : HHVM::STATUS_UNKNOWN;

        switch ($hhvmStatus) {
            case HHVM::STATUS_ALLOWED_FAILURE:
                $vertex['fillcolor'] = '#ffa500';
                break;
            case HHVM::STATUS_SUPPORTED:
                $vertex['fillcolor'] = '#00ff00';
                break;
            case HHVM::STATUS_NONE:
                $vertex['fillcolor'] = '#ff0000';
                break;
        }

        if ($maxHhvmStatus > $hhvmStatus) {
            switch ($maxHhvmStatus) {
                case HHVM::STATUS_ALLOWED_FAILURE:
                    $vertex['color'] = '#00ff00';
                    $vertex['penwidth'] = 3;
                    break;
                case HHVM::STATUS_SUPPORTED:
                    // Cant be better
                    break;
                case HHVM::STATUS_NONE:
                    $vertex['color'] = '#ffa500';
                    $vertex['penwidth'] = 3;
                    break;
            }
        }

        return $vertex;
    }

    /**
     *
     * @param string $dir
     * @return \Fhaculty\Graph\Graph
     */
    public function createGraph()
    {
        $graph = new Graph();

        foreach ($this->dependencyGraph->getPackages() as $package) {
            if ($package->isPhpExtension() || $package->isPhpExtension() || $package->getName() == 'php') {
                continue;
            }

            $name = $package->getName();
            $start = $graph->createVertex($name, true);

            $label = $name;
            if ($package->getVersion() !== null) {
                $label .= ': ' . $package->getVersion();
            }

            $start->setLayout(array('label' => $label) + $this->getLayoutVertexForPackage($package->getName(), $package->getVersion()));

            foreach ($package->getOutEdges() as $requires) {
                if ($requires->getDestPackage()->isPhpExtension() || $requires->getDestPackage()->isPhpExtension() || $requires->getDestPackage()->getName() == 'php') {
                    continue;
                }

                $targetName = $requires->getDestPackage()->getName();
                $target = $graph->createVertex($targetName, true);

                $label = $requires->getVersionConstraint();

                $edge = $start->createEdgeTo($target)->setLayout(array('label' => $label) + $this->layoutEdge);

                if ($requires->isDevDependency()) {
                    $edge->setLayout($this->layoutEdgeDev);
                }
            }
        }

        $graph->getVertex($this->dependencyGraph->getRootPackage()->getName())->setLayout($this->layoutVertexRoot);

        return $graph;
    }

    public function getImage()
    {
        $graph = $this->createGraph();
        $graphviz = new GraphViz($graph);
        $graphviz->setFormat($this->format);

        return $graphviz->createImageData();
    }

    public function getImagePath()
    {
        $graph = $this->createGraph();
        $graphviz = new GraphViz($graph);
        $graphviz->setFormat($this->format);

        return $graphviz->createImageFile();
    }

    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }
}
