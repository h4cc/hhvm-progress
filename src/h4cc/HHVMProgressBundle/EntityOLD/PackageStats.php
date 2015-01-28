<?php

namespace h4cc\HHVMProgressBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PackageStats
 *
 * @ORM\Table(
 *   uniqueConstraints={
 *      @ORM\UniqueConstraint(name="date_idx", columns={"date"})
 *   }
 * )
 * @ORM\Entity()
 */
class PackageStats
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
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date")
     */
    private $date;

    /**
     * @var array
     *
     * @ORM\Column(name="stats", type="json_array")
     */
    private $stats;


    /**
     * Get id.

     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set date.

     *
     * @param \DateTime $date
     *
     * @return PackageStats
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.

     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set stats.

     *
     * @param array $stats
     *
     * @return PackageStats
     */
    public function setStats($stats)
    {
        $this->stats = $stats;

        return $this;
    }

    /**
     * Get stats.

     *
     * @return array
     */
    public function getStats()
    {
        return $this->stats;
    }
}

