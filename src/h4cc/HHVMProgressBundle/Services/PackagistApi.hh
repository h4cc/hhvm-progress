<?hh

namespace h4cc\HHVMProgressBundle\Services;

use Packagist\Api\Client;
use Packagist\Api\Result\Package;

class PackagistApi
{
    private Client $client;

    public function __construct() {
        $this->client = new Client();
    }

    public function getAllPackageNames() : array<string> {
        return $this->client->all();
    }

    public function getInfosByName(string $name) : Package {
        return $this->client->get($name);
    }
}
