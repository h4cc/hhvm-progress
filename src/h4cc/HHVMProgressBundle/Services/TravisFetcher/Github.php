<?php


namespace h4cc\HHVMProgressBundle\Services\TravisFetcher;


use Github\Client;
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
            if('Not Found' != $e->getMessage()) {
                $this->logger->error($e->getMessage());
                $this->logger->debug($e);
            }
            // Reaching limit is a hard error.
            if(false !== stripos($e->getMessage(), 'You have reached GitHub hour limit')) {
                throw new \RuntimeException("Reached Github Limit", 0, $e);
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
 