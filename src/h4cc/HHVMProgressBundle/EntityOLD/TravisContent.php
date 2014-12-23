<?php

namespace h4cc\HHVMProgressBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TravisContent
 *
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
     * @var string
     *
     * @ORM\Column(name="package_name", type="string", length=255)
     */
    private $package_name;


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
     * @var string
     *
     * @ORM\Column(name="travis_content", type="text")
     */
    private $travis_content;

    /**
     * Returns a list of all possible hhvm status.
     *
     * @return array
     */
    public static function getAllHHVMStatus()
    {
        return array(
            self::HHVM_STATUS_NONE,
            self::HHVM_STATUS_ALLOWED_FAILURE,
            self::HHVM_STATUS_NO_PHP,
            self::HHVM_STATUS_SUPPORTED,
            self::HHVM_STATUS_UNKNOWN,
        );
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

    public function getHhvmStatusAsString()
    {
        switch($this->hhvm_status) {
            case static::HHVM_STATUS_ALLOWED_FAILURE:
                return 'partial_tested';
            case static::HHVM_STATUS_NO_PHP:
                return 'not_php';
            case static::HHVM_STATUS_NONE:
                return 'not_tested';
            case static::HHVM_STATUS_SUPPORTED:
                return 'tested';
            case static::HHVM_STATUS_UNKNOWN:
                return 'unknown';
        }
        throw new \RuntimeException("Unknown HHVM status: ".$this->hhvm_status);
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

    /**
     * @param string $travis_content
     */
    public function setTravisContent($travis_content)
    {
        $this->travis_content = $travis_content;
    }

    /**
     * @return string
     */
    public function getTravisContent()
    {
        return $this->travis_content;
    }

    /**
     * @param \DateTime $time
     */
    public function setTime(\DateTime $time)
    {
        $this->time = $time;
    }

    /**
     * @return \DateTime
     */
    public function getTime()
    {
        return $this->time;
    }
}
