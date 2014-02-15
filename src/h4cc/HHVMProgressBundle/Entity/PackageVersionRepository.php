<?php

namespace h4cc\HHVMProgressBundle\Entity;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;


class PackageVersionRepository
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

    public function getByName($name) {
        /** @var \Doctrine\ORM\QueryBuilder $query */
        $query = $this->repo->createQueryBuilder('v');

        $query->select('v');
        $query->where('v.name = ?1');
        $query->orderBy('v.version', 'DESC');

        $query->setParameter(1, $name);

        return $query->getQuery()->getResult();
    }

    public function getAllByHHVMStatus($hhvmStatus) {
        /** @var \Doctrine\ORM\QueryBuilder $query */
        $query = $this->repo->createQueryBuilder('v');

        $query->select('v');
        $query->where('v.hhvm_status = ?1');
        $query->orderBy('v.name');
        $query->groupBy('v.name');

        $query->setParameter(1, $hhvmStatus);

        return $query->getQuery()->getResult();
    }

    public function getMaxHHVMStatusForNames() {
        /** @var \Doctrine\ORM\QueryBuilder $query */
        $query = $this->repo->createQueryBuilder('v');

        $query->select('v.name, MAX(v.hhvm_status) AS max_hhvm_status');
        $query->groupBy('v.name');

        $result = array();
        foreach($query->getQuery()->getResult() as $row) {
            $result[$row['name']] = $row['max_hhvm_status'];
        }

        return $result;
    }

    public function exists($name, $versionString, $gitReference) {
        $version = $this->repo->findOneBy(array(
          'name' => $name,
          'version' => $versionString,
          'git_reference' => $gitReference,
        ));
        if(!$version) {
            return false;
        }
        $this->om->detach($version);
        return true;
    }

    public function removeByNameAndVersion($name, $versionString) {
        $version = $this->repo->findOneBy(array(
          'name' => $name,
          'version' => $versionString,
        ));
        if($version) {
            $this->om->remove($version);
            $this->om->flush();
        }
    }

    public function get($name, $version) {
        return $this->repo->findOneBy(array(
          'name' => $name,
          'version' => $version,
        ));
    }

    public function add($name, $type, $description, $version, $gitReference, $status) {
        $paketVersion = new PackageVersion();
        $paketVersion->setName($name);
        $paketVersion->setType($type ? $type : 'library');
        $paketVersion->setDescription($description);
        $paketVersion->setVersion($version);
        $paketVersion->setGitReference($gitReference);
        $paketVersion->setHhvmStatus($status);

        $this->om->persist($paketVersion);
        $this->om->flush();
    }
}
