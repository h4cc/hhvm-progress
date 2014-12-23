<?hh

namespace h4cc\HHVMProgressBundle\Entity;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

class PackageVersionRepository
{
    private ObjectRepository $repo;

    private ObjectManager $om;

    public function __construct(ObjectRepository $repo, ObjectManager $om) {
        $this->repo = $repo;
        $this->om = $om;
    }

    public function getByPackageAndVersion(Package $package, string $version) : ?PackageVersion
    {
        return $this->repo->findOneBy(['package' => $package, 'version' => $version]);
    }

    public function getByPackageNameAndVersion(string $packageName, string $versionNormalized) : ?PackageVersion
    {
        $query = $this->repo->createQueryBuilder('pv');

        $query->select('pv');
        $query->join('pv.package', 'p');

        $query->where('p.name = :name');
        $query->andWhere('pv.versionNormalized = :version');

        $query->setParameter('name', $packageName);
        $query->setParameter('version', $versionNormalized);

        return $query->getQuery()->getOneOrNullResult();
    }

    public function save(Packageversion $packageVersion) {
        $this->om->persist($packageVersion);
        $this->om->flush();
    }

    public function remove(Packageversion $packageVersion) {
        $this->om->remove($packageVersion);
        $this->om->flush();
    }
}