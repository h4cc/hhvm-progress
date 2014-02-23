<?php

namespace h4cc\HHVMProgressBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PackageVersion
 *
 * @ORM\Table(uniqueConstraints={
 *  @ORM\UniqueConstraint(name="name_version_unique",columns={"name", "version"})
 * })
 * @ORM\Entity()
 */
class PackageVersion
{
    /** HHVM is not in travis.yml */
    const HHVM_STATUS_NONE = 1;

    /** HHVM is a allowed failure build. */
    const HHVM_STATUS_ALLOWED_FAILURE = 2;

    /** HHVM is a full build. */
    const HHVM_STATUS_SUPPORTED = 3;

    /** Not a PHP build. */
    const HHVM_STATUS_NO_PHP = -1;

    /** Could not determine status, like missing travis.yml */
    const HHVM_STATUS_UNKNOWN = -2;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     */
    private $description = '';

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=255)
     */
    private $version;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="hhvm_status", type="integer")
     */
    private $hhvm_status;

    /**
     * @var string
     *
     * @ORM\Column(name="git_reference", type="string", length=255)
     */
    private $git_reference;


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
     * Set name
     *
     * @param string $name
     * @return PackageVersion
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set version
     *
     * @param string $version
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
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set hhvmStatus
     *
     * @param integer $hhvmStatus
     * @return PackageVersion
     */
    public function setHhvmStatus($hhvmStatus)
    {
        $this->hhvm_status = $hhvmStatus;

        return $this;
    }

    /**
     * Get hhvmStatus
     *
     * @return integer 
     */
    public function getHhvmStatus()
    {
        return $this->hhvm_status;
    }

    /**
     * @param string $git_reference
     */
    public function setGitReference($git_reference)
    {
        $this->git_reference = $git_reference;
    }

    /**
     * @return string
     */
    public function getGitReference()
    {
        return $this->git_reference;
    }
}
