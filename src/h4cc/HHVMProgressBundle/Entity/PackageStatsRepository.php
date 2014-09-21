<?php

namespace h4cc\HHVMProgressBundle\Entity;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;


class PackageStatsRepository
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository
     */
    private $repo;

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $om;

    public function __construct(ObjectRepository $repo, ObjectManager $om) {
        $this->repo = $repo;
        $this->om = $om;
    }

    public function fetchAll()
    {
        return $this->repo->findBy(
            array(),
            array('date' => 'ASC')
        );
    }

    public function saveStats(\DateTime $date, array $stats)
    {
        $entity = $this->repo->findOneBy(array('date' => $date));

        if(!$entity) {
            $entity = new PackageStats();
            $entity->setDate($date);
        }

        $entity->setStats($stats);

        $this->om->persist($entity);
        $this->om->flush();
    }
}
