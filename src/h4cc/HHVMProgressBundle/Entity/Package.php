<?php

namespace h4cc\HHVMProgressBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Package
 *
 * @ORM\Table(uniqueConstraints={
 *  @ORM\UniqueConstraint(name="name_unique",columns={"name"})
 * })
 * @ORM\Entity()
 */
class Package
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
     * @var \DateTime
     *
     * @ORM\Column(name="time", type="datetime")
     */
    private $time;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    private $type = 'unknown';

    /**
     * @var
     *
     * @ORM\OneToMany(targetEntity="h4cc\HHVMProgressBundle\Entity\PackageVersion", mappedBy="package", cascade={"remove", "persist"})
     * @ORM\OrderBy({"versionNormalized" = "DESC"})
     */
    private $versions;

    /**
     * @var
     *
     * @ORM\OneToMany(targetEntity="h4cc\HHVMProgressBundle\Entity\TravisContent", mappedBy="package", cascade={"remove", "persist"})
     */
    private $travisContents;

    public function __construct()
    {
        $this->versions = new ArrayCollection();
        $this->travisContents = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getVersions()
    {
        return $this->versions;
    }

    /**
     * @param mixed $versions
     */
    public function setVersions($versions)
    {
        $this->versions = $versions;
    }

    /**
     * @return mixed
     */
    public function getTravisContents()
    {
        return $this->travisContents;
    }

    /**
     * @param mixed $travisContents
     */
    public function setTravisContents($travisContents)
    {
        $this->travisContents = $travisContents;
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
     *
     * @return Package
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
     * Set description
     *
     * @param string $description
     *
     * @return Package
     */
    public function setDescription($description)
    {
        $this->description = (string)$description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set time
     *
     * @param \DateTime $time
     *
     * @return Package
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get time
     *
     * @return \DateTime
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Package
     */
    public function setType($type)
    {
        $this->type = (string)$type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function __toString()
    {
        return 'Package: ' . $this->id . ' ' . $this->name;
    }
}

