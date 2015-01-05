<?hh

namespace h4cc\HHVMProgressBundle\Entity;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use h4cc\HHVMProgressBundle\HHVM;

class TravisContentRepository
{
    private ObjectRepository $repo;

    private ObjectManager $om;

    public function __construct(ObjectRepository $repo, ObjectManager $om) {
        $this->repo = $repo;
        $this->om = $om;
    }

    public function getByPackageAndSourceReference(Package $package, string $sourceReference) : ?TravisContent
    {
        return $this->repo->findOneBy(['package' => $package, 'sourceReference' => $sourceReference]);
    }

    public function getAllByMaxHHVMStatus(int $hhvmStatus) : array<Package> {
        /** @var \Doctrine\ORM\QueryBuilder $query */
        $query = $this->repo->createQueryBuilder('tc');

        $query->select('tc', 'p');
        $query->leftJoin('tc.package', 'p');

        $query->groupBy('tc.package');
        $query->having('MAX(tc.hhvm_status) = ?1');
        $query->orderBy('p.name', 'ASC');

        $query->setParameter(1, $hhvmStatus);

        return $query->getQuery()->getResult();
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

    public function getMaxHHVMStatusCount() : array<string, int>
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

    public function all() {
        return $this->repo->findAll();
    }

    public function save(TravisContent $travisContent) {
        $this->om->persist($travisContent);
        $this->om->flush();
    }
}