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

    public function findWhereNameContains($pattern) {
        /** @var \Doctrine\ORM\QueryBuilder $query */
        $query = $this->repo->createQueryBuilder('v');

        $query->select('v');
        $query->where('v.name LIKE :pattern');
        $query->orderBy('v.name', 'ASC');
        $query->groupBy('v.name');

        $query->setParameter('pattern', "%$pattern%");

        return $query->getQuery()->getResult();
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

    public function getAllForNameWhereVersionNot($name, array $versions) {
        /** @var \Doctrine\ORM\QueryBuilder $query */
        $query = $this->repo->createQueryBuilder('v');

        $query->where($query->expr()->eq('v.name', ':name'));
        $query->setParameter('name', $name);

        $query->andWhere($query->expr()->notIn('v.version', ':versions'));
        $query->setParameter('versions', $versions);

        return $query->getQuery()->getResult();
    }

    /**
     * Returns all Packages, where the HHVM status is at MAX $hhvmStatus.
     *
     * @param $hhvmStatus
     * @return array
     */
    public function getAllByMaxHHVMStatus($hhvmStatus) {
        /** @var \Doctrine\ORM\QueryBuilder $query */
        $query = $this->repo->createQueryBuilder('v');

        $query->select('v');
        $query->orderBy('v.name');
        $query->groupBy('v.name');
        $query->having('MAX(v.hhvm_status) = ?1');

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
            $this->remove($version);
        }
    }

    /**
     * @param $name
     * @param $version
     * @return PackageVersion
     */
    public function get($name, $version) {
        return $this->repo->findOneBy(array(
          'name' => $name,
          'version' => $version,
        ));
    }

    public function add($name, $type, $description, $time, $version, $gitReference, $status, $travisContent) {
        $paketVersion = new PackageVersion();
        $paketVersion->setName($name);
        $paketVersion->setType($type ? $type : 'library');
        $paketVersion->setDescription($description);
        $paketVersion->setTime(new \DateTime($time));
        $paketVersion->setVersion($version);
        $paketVersion->setGitReference($gitReference);
        $paketVersion->setHhvmStatus($status);
        $paketVersion->setTravisContent($travisContent);

        $this->om->persist($paketVersion);
        $this->om->flush();
    }

    public function remove(PackageVersion $version) {
        $this->om->remove($version);
        $this->om->flush();
    }

    public function getAllPackageNames()
    {
        /** @var \Doctrine\ORM\QueryBuilder $query */
        $query = $this->repo->createQueryBuilder('v');

        $query->select('v.name');
        $query->groupBy('v.name');

        $result = array();
        foreach($query->getQuery()->getResult() as $row) {
            $result[] = $row['name'];
        }

        return $result;
    }

    /**
     * @param $date string like '2014-09-01'
     * @return PackageVersion[]
     */
    public function getMaxHHVMStatusOnDay($date)
    {
        /** @var \Doctrine\ORM\QueryBuilder $query */
        $query = $this->repo->createQueryBuilder('v');

        $query->select('v');
        $query->where('DATE(v.time) = ?1');
        $query->groupBy('v.name');
        $query->having('v.hhvm_status >= MAX(v.hhvm_status)');

        $query->setParameter(1, $date);

        return $query->getQuery()->getResult();

    }
}
