<?hh

namespace h4cc\HHVMProgressBundle\Entity;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

class PackageRepository
{
    private ObjectRepository $repo;

    private ObjectManager $om;

    public function __construct(ObjectRepository $repo, ObjectManager $om) {
        $this->repo = $repo;
        $this->om = $om;
    }

    public function getByName(string $name) : ?Package {
        return $this->repo->findOneByName($name);
    }

    public function searchByNamePattern(string $pattern) : array<Package> {
        /** @var \Doctrine\ORM\QueryBuilder $query */
        $query = $this->repo->createQueryBuilder('p');

        $query->select('p');
        $query->where('p.name LIKE :pattern');
        $query->orderBy('p.name', 'ASC');

        $query->setParameter('pattern', '%'.$pattern.'%');

        return $query->getQuery()->getResult();
    }

    public function save(Package $package) {
        $this->om->persist($package);
        $this->om->flush();
    }
}