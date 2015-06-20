<?php

namespace h4cc\HHVMProgressBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PackageVersion
 *
 * @ORM\Table(uniqueConstraints={
 *  @ORM\UniqueConstraint(name="name_version_unique",columns={"package_id", "version"})
 * })
 * @ORM\Entity()
 */
class PackageVersion
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Package
     *
     * @ORM\ManyToOne(targetEntity="h4cc\HHVMProgressBundle\Entity\Package", inversedBy="versions", cascade={"persist"})
     * @ORM\JoinColumn(name="package_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $package;

    /**
     * @var TravisContent
     *
     * @ORM\ManyToOne(targetEntity="h4cc\HHVMProgressBundle\Entity\TravisContent", cascade={"persist"})
     * @ORM\JoinColumn(name="travisContent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $travisContent;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=255)
     */
    private $version;

    /**
     * @var string
     *
     * @ORM\Column(name="versionNormalized", type="string", length=255)
     */
    private $versionNormalized;

    /**
     * @var string
     *
     * @ORM\Column(name="source_reference", type="string", length=255)
     */
    private $sourceReference;

    public function __construct(Package $package, TravisContent $travisContent)
    {
        $this->setPackage($package);
        $this->setTravisContent($travisContent);
    }

    /**
     * @return int
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * @param int $package
     */
    public function setPackage(Package $package)
    {
        $this->package = $package;
    }

    /**
     * @return mixed
     */
    public function getTravisContent()
    {
        return $this->travisContent;
    }

    /**
     * @param mixed $travisContent
     */
    public function setTravisContent(TravisContent $travisContent)
    {
        $this->travisContent = $travisContent;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set version
     *
     * @param string $version
     *
     * @return PackageVersion
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set versionNormalized
     *
     * @param string $versionNormalized
     *
     * @return PackageVersion
     */
    public function setVersionNormalized($versionNormalized)
    {
        $this->versionNormalized = $versionNormalized;

        return $this;
    }

    /**
     * Get versionNormalized
     *
     * @return string
     */
    public function getVersionNormalized()
    {
        return $this->versionNormalized;
    }

    public function setSourceReference($sourceReference)
    {
        $this->sourceReference = $sourceReference;

        return $this;
    }

    /**
     * Get sorceReference
     *
     * @return string
     */
    public function getSourceReference()
    {
        return $this->sourceReference;
    }

    public function __toString()
    {
        return 'PackageVersion: ' . $this->id . ' ' . $this->version . ' Package: ' . $this->package->getName();
    }
}

