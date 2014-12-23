<?hh

namespace h4cc\HHVMProgressBundle\Services;

use h4cc\HHVMProgressBundle\Entity\PackageVersion;
use h4cc\HHVMProgressBundle\Exception\GithubAuthErrorException;
use h4cc\HHVMProgressBundle\Exception\GithubRateLimitException;
use Packagist\Api\Result\Package\Source;
use Psr\Log\LoggerInterface;
use Github\Client as GithubClient;

class TravisFetcher
{
    private LoggerInterface $logger;
    private GithubClient $github;

    public function __construct(LoggerInterface $logger, GithubClient $github) {
        $this->logger = $logger;
        $this->github = $github;
    }

    public function fetchTravisContentFromSource(Source $source)
    {
        if('git' != $source->getType()) {
            $this->logger->info('Not a Git source');

            return false;
        }

        $url = $source->getUrl();

        // Try to fetch from github
        if(false !== stripos($url, 'github.com')) {
            if(preg_match('@github.com/(.+)/(.+)@', $url, $matches)) {
                $user = $matches[1];
                $repo = basename($matches[2], '.git');
                $this->logger->debug("Fetching travis file from github for $user/$repo.");

                return $this->fetchGithub($user, $repo, $source->getReference());
            }
        }

        $this->logger->info('Not a GitHub source');

        return false;
    }

    public function fetchGithub($user, $repo, $branch) {

        // Fetch content
        $api = $this->github->api('repos')->contents();
        try {
            $travisConfig = $api->show($user, $repo, '.travis.yml', $branch);
        }catch(\Github\Exception\RuntimeException $e) {

            // Auth error
            if('Bad credentials' == $e->getMessage()) {
                throw new GithubAuthErrorException("Github Auth failed", 0, $e);
            }

            // Reaching limit is a hard error.
            if(false !== stripos($e->getMessage(), 'You have reached GitHub hour limit')) {
                throw new GithubRateLimitException("Reached Github Limit", 0, $e);
            }

            if('Not Found' == $e->getMessage() || 0 === stripos($e->getMessage(), 'No commit found')) {
                $this->logger->info($e->getMessage());
            }else{
                $this->logger->warning($e->getMessage());
                $this->logger->debug($e);
            }

            return false;
        }

        // Convert content
        $content = $travisConfig['content'];
        if('base64' == $travisConfig['encoding']) {
            $content = base64_decode($content);
        }

        return [
            'content' => $content,
            'ref' => $travisConfig['sha'],
        ];
    }
}
