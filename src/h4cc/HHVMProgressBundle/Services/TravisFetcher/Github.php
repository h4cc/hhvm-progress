<?php


namespace h4cc\HHVMProgressBundle\Services\TravisFetcher;


use Github\Client;
use h4cc\HHVMProgressBundle\Exception\GithubAuthErrorException;
use h4cc\HHVMProgressBundle\Exception\GithubRateLimitException;
use Psr\Log\LoggerInterface;

class Github
{
    /**
     * @var \Github\Client
     */
    private $client;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(Client $client, LoggerInterface $logger) {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function fetch($user, $repo, $branch) {

        // Fetch content
        $api = $this->client->api('repos')->contents();
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

            if('Not Found' != $e->getMessage()) {
                $this->logger->error($e->getMessage());
                $this->logger->debug($e);
            }

            return false;
        }
        // Convert content
        $content = $travisConfig['content'];
        if('base64' == $travisConfig['encoding']) {
            $content = base64_decode($content);
        }
        return $content;
    }
}
 