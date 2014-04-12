<?php


namespace h4cc\HHVMProgressBundle\Services;


use h4cc\HHVMProgressBundle\Entity\PackageVersion;
use h4cc\HHVMProgressBundle\Services\TravisFetcher\Github;
use Packagist\Api\Result\Package\Version;
use Packagist\Api\Result\Package\Source;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;

class TravisFetcher
{
    /** @var \h4cc\HHVMProgressBundle\Services\TravisFetcher\Github  */
    private $github;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /** @var  string */
    private $content = '';

    public function __construct(LoggerInterface $logger, Github $github) {
        $this->github = $github;
        $this->logger = $logger;
    }

    public function getTravisFileContent()
    {
        return $this->content;
    }

    public function fetchTravisHHVMStatus(Version $version) {
        $this->content = '';

        /** @var Source $source */
        $source = $version->getSource();

        if('git' == $source->getType()) {
            $url = $source->getUrl();

            // Try to fetch from github
            if(false !== stripos($url, 'github.com')) {
                if(preg_match('@github.com/(.+)/(.+)@', $url, $matches)) {
                    $user = $matches[1];
                    $repo = basename($matches[2], '.git');
                    $this->logger->debug("Fetching travis file from github for $user/$repo.");
                    $this->content = $this->github->fetch($user, $repo, $source->getReference());
                }
            }
        }

        // Fetcher can return 'false', what means fetching failed.
        if(false === $this->content) {
            throw new \RuntimeException("Fetching travis file failed.");
        }

        $this->logger->debug("Fetched .travis.yml content: '$this->content'");

        return $this->getHHVMStatusFromTravisConfig($this->content);
    }

    protected function getHHVMStatusFromTravisConfig($content) {
        if(!$content) {
            // If there was no exception, there was simply no travis.yml file.
            return PackageVersion::HHVM_STATUS_UNKNOWN;
        }

        try {
            $data = Yaml::parse($content);
        }catch(\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->logger->debug($e);

            // We cant know, so this will be "none"
            return PackageVersion::HHVM_STATUS_UNKNOWN;
        }

        // Check language.
        if(isset($data['language']) && 'php' == $data['language']) {
            // This is a PHP build, so keep on.
        }else{
            return PackageVersion::HHVM_STATUS_NO_PHP;
        }

        // Check php versions.
        if(isset($data['php'])) {
            if(is_array($data['php'])) {
                $supports = in_array('hhvm', $data['php']);
            }else{
                $supports = ('hhvm' == $data['php']);
            }
        }else{
            return PackageVersion::HHVM_STATUS_NO_PHP;
        }

        // Check allowed failure matrix.
        if($supports && isset($data['matrix']) && isset($data['matrix']['allow_failures'])) {
            $af = $data['matrix']['allow_failures'];
            foreach($af as $keyValue) {
                if(is_array($keyValue) && isset($keyValue['php'])) {
                    if('hhvm' == $keyValue['php']) {
                        return PackageVersion::HHVM_STATUS_ALLOWED_FAILURE;
                    }
                }
            }
        }

        return ($supports) ? PackageVersion::HHVM_STATUS_SUPPORTED : PackageVersion::HHVM_STATUS_NONE;
    }
}
