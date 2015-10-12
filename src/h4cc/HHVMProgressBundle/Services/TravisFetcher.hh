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
    private array $githubTokens;

    public function __construct(LoggerInterface $logger, GithubClient $github, array $githubTokens) {
        $this->logger = $logger;
        $this->github = $github;
        $this->githubTokens = $githubTokens;
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
            $matches = null;
            if(preg_match('@github.com/(.+)/(.+)@', $url, $matches)) {
                if(!is_null($matches)) {
                    $user = $matches[1];
                    $repo = basename($matches[2], '.git');
                    $this->logger->debug("Fetching travis file from github for $user/$repo.");

                    return $this->fetchGithub($user, $repo, $source->getReference());
                }
            }
        }

        $this->logger->info('Not a GitHub source');

        return false;
    }

    public function fetchGithub($user, $repo, $branch) {
        try {
            return $this->fetchGithubWithoutRetry($user, $repo, $branch);
        }
        catch(GithubAuthErrorException $e) {
            $this->logger->info('Using next github token because of GithubAuthErrorException.');
            $this->logger->debug($e);
            if(!$this->useNextGithubToken()) {
                throw $e;
            }
        }
        catch(GithubRateLimitException $e) {
            $this->logger->info('Using next github token because of GithubRateLimitException.');
            $this->logger->debug($e);
            if(!$this->useNextGithubToken()) {
                throw $e;
            }
        }

        // Retry with new github token.
        return $this->fetchGithub($user, $repo, $branch);
    }

    private function useNextGithubToken() {
        if(!$this->githubTokens) {
            return false;
        }

        // Using a random token and remove it from array.
        $randomKey = array_rand($this->githubTokens, 1);
        $nextToken = $this->githubTokens[$randomKey];
        unset($this->githubTokens[$randomKey]);

        $this->logger->debug('Using GithubToken: '. $nextToken);

        $this->github->authenticate($nextToken, "http_token");

        return true;
    }

    private function fetchGithubWithoutRetry($user, $repo, $branch) {

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

            if('Not Found' == $e->getMessage() || 0 === stripos($e->getMessage(), 'No commit found') || 0 === stripos($e->getMessage(), 'This repository is empty')) {
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
