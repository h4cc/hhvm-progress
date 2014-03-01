<?php


namespace h4cc\HHVMProgressBundle\Graph;

use Composer\Package\Version\VersionParser;
use Fhaculty\Graph\GraphViz;
use Fhaculty\Graph\Graph;

use h4cc\HHVMProgressBundle\Entity\PackageVersion;
use h4cc\HHVMProgressBundle\Entity\PackageVersionRepository;

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

    private $versionParser;

    public function __construct($composerContent, $composerLockContent, $includeDevs = true)
    {
        $this->versionParser = new VersionParser();

        $analyzer = new \JMS\Composer\DependencyAnalyzer();
        $this->dependencyGraph = $analyzer->analyzeComposerData($composerContent, $composerLockContent, null, $includeDevs);
    }

    public function setPackageVersionRepo(PackageVersionRepository $repository)
    {
        $this->repository = $repository;
    }

    private function getLayoutVertexForPackage($name, $version)
    {
        if($version) {
            // Need to normalize version to find it in the database.
            $version = $this->versionParser->normalize($version);
        }

        $vertex = $this->layoutVertex;

        if(0 === stripos($name, 'symfony/')) {
            $name = 'symfony/symfony';
        }

        /** @var PackageVersion $packageVersion */
        $packageVersion = $this->repository->get($name, $version);

        if(!$packageVersion) {
            return $vertex;
        }

        switch($packageVersion->getHhvmStatus()) {
            case PackageVersion::HHVM_STATUS_ALLOWED_FAILURE:
                $vertex['fillcolor'] = '#ffa500';
                break;
            case PackageVersion::HHVM_STATUS_SUPPORTED:
                $vertex['fillcolor'] = '#00ff00';
                break;
            case PackageVersion::HHVM_STATUS_NONE:
                $vertex['fillcolor'] = '#ff0000';
                break;
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
            if($package->isPhpExtension() || $package->isPhpExtension() || $package->getName() == 'php') {
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
                if($requires->getDestPackage()->isPhpExtension() || $requires->getDestPackage()->isPhpExtension() || $requires->getDestPackage()->getName() == 'php') {
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
