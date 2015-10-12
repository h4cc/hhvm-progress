<?hh

namespace h4cc\HHVMProgressBundle\Services;

use FastFeed\Factory;
use FastFeed\FastFeed;

class PackagistFeeds
{
    private FastFeed $feeds;

    public function __construct() {
        $this->feeds = Factory::create();
        $this->feeds->addFeed('packages', 'https://packagist.org/feeds/packages.rss');
        $this->feeds->addFeed('releases', 'https://packagist.org/feeds/releases.rss');
    }

    public function getRecentPackageNames() : array<string> {
        $names = [];

        $packages = $this->feeds->fetch('packages');
        $releases = $this->feeds->fetch('releases');

        foreach($packages as $package) {
            $names[] = $package->getName();
        }

        foreach($releases as $release) {
            $name = $release->getName();
            // Need to extract package name via regex.
            $matches = null;
            if(preg_match('@^(.+)\/(.+) \(.+\)$@', $name, $matches)) {
                if(!is_null($matches)) {
                    $names[] = $matches[1].'/'.$matches[2];
                }
            }
        }

        return array_unique($names);
    }
}
