<?php

namespace h4cc\HHVMProgressBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use h4cc\HHVMProgressBundle\HHVM;

/**
 * TravisContent
 *
 * @ORM\Table(uniqueConstraints={
 *  @ORM\UniqueConstraint(name="package_sourceref_unique",columns={"package_id", "source_reference"})
 * })
 * @ORM\Entity()
 */
class TravisContent
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
     * @ORM\ManyToOne(targetEntity="h4cc\HHVMProgressBundle\Entity\Package", inversedBy="travisContents", cascade={"persist"})
     * @ORM\JoinColumn(name="package_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $package;

    /**
     * @var string
     *
     * @ORM\Column(name="source_reference", type="string", length=255, nullable=true)
     */
    private $sourceReference;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;

    /**
     * @var integer
     *
     * @ORM\Column(name="hhvm_status", type="integer")
     */
    private $hhvm_status = HHVM::STATUS_UNKNOWN;

    /**
     * @var
     *
     * @ORM\Column(name="file_exists", type="boolean")
     */
    private $fileExists = false;

    public function __construct(Package $package)
    {
        $this->setPackage($package);
    }

    /**
     * @return int
     */
    public function getHhvmStatus()
    {
        return $this->hhvm_status;
    }

    /**
     * @param int $hhvm_status
     */
    public function setHhvmStatus($hhvm_status)
    {
        $this->hhvm_status = $hhvm_status;
    }

    /**
     * @return mixed
     */
    public function getHhvmStatusString()
    {
        return HHVM::getStringForStatus($this->hhvm_status);
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
    public function getFileExists()
    {
        return $this->fileExists;
    }

    /**
     * @param mixed $fileExists
     */
    public function setFileExists($fileExists)
    {
        $this->fileExists = $fileExists;
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
     * Set sourceReference
     *
     * @param string $sourceReference
     *
     * @return TravisContent
     */
    public function setSourceReference($sourceReference)
    {
        $this->sourceReference = $sourceReference;

        return $this;
    }

    /**
     * Get sourceReference
     *
     * @return string
     */
    public function getSourceReference()
    {
        return $this->sourceReference;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return TravisContent
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    public function __toString()
    {
        return 'TravisContent: ' . $this->id . ' ' . $this->package->getname() . ' ' . $this->sourceReference;
    }
}

