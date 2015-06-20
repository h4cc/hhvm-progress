<?hh

namespace h4cc\HHVMProgressBundle\Entity;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\AbstractQuery;
use h4cc\HHVMProgressBundle\HHVM;

class TravisContentRepository
{
    private ObjectRepository $repo;
    private ObjectRepository $repoPackages;

    private ObjectManager $om;

    public function __construct(ObjectRepository $repo, ObjectManager $om, ObjectRepository $repoPackages) {
        $this->repo = $repo;
        $this->om = $om;
        $this->repoPackages = $repoPackages;
    }

    public function getByPackageAndSourceReference(Package $package, string $sourceReference) : ?TravisContent
    {
        return $this->repo->findOneBy(['package' => $package, 'sourceReference' => $sourceReference]);
    }

    public function getAllByMaxHHVMStatus(int $hhvmStatus) : array<Package> {
        return $this->getAllByMaxHHVMStatusQuery($hhvmStatus)->getResult();
    }

    public function getAllByMaxHHVMStatusQuery(int $hhvmStatus) : AbstractQuery
    {
        $packageIds = $this->getPackageIdsByMaxHHVMStatus($hhvmStatus);

        /** @var \Doctrine\ORM\QueryBuilder $query */
        $query = $this->repoPackages->createQueryBuilder('p');

        $query->select('p');

        $query->where('p.id IN (:ids)');
        $query->setParameter('ids', $packageIds);

        $query->orderBy('p.name', 'ASC');

        return $query
            ->getQuery()
            ->useResultCache(true, 3600, __CLASS__.':'.__METHOD__.':hhvmStatus:'.$hhvmStatus)
        ;
    }

    private function getPackageIdsByMaxHHVMStatus(int $hhvmStatus) : array<int>
    {
        /** @var \Doctrine\ORM\QueryBuilder $query */
        $query = $this->repo->createQueryBuilder('tc');

        $query->select(['IDENTITY(tc.package) as package_id']);

        $query->where('tc.hhvm_status = ?1');
        $query->setParameter(1, $hhvmStatus);

        $query->groupBy('tc.package');

        $result = $query
            ->getQuery()
            ->useResultCache(true, 600, __CLASS__.':'.__METHOD__.':hhvmStatus:'.$hhvmStatus)
            ->getArrayResult()
        ;

        $packageIds = array_map(function(array $row) {
            return (int)$row['package_id'];
        }, $result);

        return $packageIds;
    }

    public function getMaxHHVMStatusForNames() {
        /** @var \Doctrine\ORM\QueryBuilder $query */
        $query = $this->repo->createQueryBuilder('tc');

        $query->select('p.name', 'MAX(tc.hhvm_status) as max_hhvm_status');
        $query->leftJoin('tc.package', 'p');

        $query->groupBy('tc.package');
        $query->orderBy('p.name', 'ASC');

        $result = array();
        foreach($query->getQuery()->getResult() as $row) {
            $result[$row['name']] = (int)$row['max_hhvm_status'];
        }

        return $result;
    }

    public function getMaxHHVMStatusCountNumeric() : array<int, int>
    {
        $query = $this->getMaxHHVMStatusQuery();
        $query->useResultCache(true, 3600, __CLASS__.':'.__METHOD__);

        // Init array with all possible hhvm status.
        $result = array();
        foreach(HHVM::getAllHHVMStatus() as $status) {
            $result[$status] = 0;
        }

        $total = 0;
        foreach($query->getResult() as $row) {
            $total += (int)$row['count'];
            $result[(int)$row['max_hhvm']] = (int)$row['count'];
        }
        $result['total'] = $total;

        return $result;
    }

    public function getMaxHHVMStatusCount() : array<string, int>
    {
        $query = $this->getMaxHHVMStatusQuery();

        // Init array with all possible hhvm status.
        $result = array();
        foreach(HHVM::getAllHHVMStatus() as $status) {
            $result[HHVM::getStringForStatus($status)] = 0;
        }

        $total = 0;
        foreach($query->getResult() as $row) {
            $total += (int)$row['count'];
            $result[HHVM::getStringForStatus((int)$row['max_hhvm'])] = (int)$row['count'];
        }
        $result['total'] = $total;

        return $result;
    }

    private function getMaxHHVMStatusQuery() : AbstractQuery
    {
        $sql = '
            select count(sub.package_id) as `count`, sub.max_hhvm
            from (
                select tc.package_id, max(tc.hhvm_status) as max_hhvm
                from TravisContent tc
                group by tc.package_id
            ) as sub
            group by sub.max_hhvm
        ';

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('count', 'count');
        $rsm->addScalarResult('max_hhvm', 'max_hhvm');

        $query = $this->om->createNativeQuery($sql, $rsm);
        $query->useResultCache(true, 60, __CLASS__.':'.__METHOD__);

        return $query;
    }

    public function all() {
        return $this->repo->findAll();
    }

    public function save(TravisContent $travisContent) {
        $this->om->persist($travisContent);
        $this->om->flush();
    }
}