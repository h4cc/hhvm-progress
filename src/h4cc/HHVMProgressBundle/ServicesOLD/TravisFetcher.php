<?php


namespace h4cc\HHVMProgressBundle\Services;


use h4cc\HHVMProgressBundle\Entity\PackageVersion;
use h4cc\HHVMProgressBundle\Exception\TravisFileMissingException;
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

    /** @var array available hhvm versions on travis */
    private $hhvmStrings = array('hhvm', 'hhvm-nightly');

    /** @var array hhvm build string found on travis.yml */
    protected $hhvmBuilds = array();

    /** @var array hhvm strings found in allowed failure travis.yml */
    protected $hhvmAllowedFailure = array();

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
            $this->logger->debug("Fetching travis file failed.");
            throw new TravisFileMissingException();
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
            $this->logger->debug($e->getMessage());
            $this->logger->debug($e);

            // We cant know, so this will be "none"
            return PackageVersion::HHVM_STATUS_UNKNOWN;
        }

        // Check language.
        if(isset($data['language']) && 'php' != $data['language']) {
            // This is NOT a PHP build, so return.
            return PackageVersion::HHVM_STATUS_NO_PHP;
        }

        // Check php versions.
        if(!isset($data['php'])) {
            // No php versions are set in this travis file, weird..
            return PackageVersion::HHVM_STATUS_NO_PHP;
        }

        // refactor here, if data is a string convert to a single array and DRY
        if (!is_array($data['php'])) {
            $data['php'] = array($data['php']);
        }

        $supports = false;
        foreach($data['php'] as $phpVersion) {
            if($this->isHHVMString($phpVersion)) {
                $supports = true;
                $this->hhvmBuilds[] = $phpVersion;
            }
        }

        // Check allowed failure matrix.
        if($supports && isset($data['matrix']) && isset($data['matrix']['allow_failures'])) {
            $af = $data['matrix']['allow_failures'];
            foreach($af as $keyValue) {
                if(is_array($keyValue) && isset($keyValue['php'])) {
                    // refactor and DRY
                    if (!is_array($keyValue['php'])) {
                        $keyValue['php'] = array($keyValue['php']);
                    }

                    foreach($keyValue['php'] as $phpString) {
                        if($this->isHHVMString($phpString)) {
                            $this->hhvmAllowedFailure[] = $phpString;
                        }
                    }
                }
            }
        }

        // array with hhvm string of not allowed hhvm builds
        // for now if at least one hhvm string is not allowed to fail, we'll mark
        // the project as tested.
        $hhvmNonFailureAllowed = array_diff($this->hhvmBuilds, $this->hhvmAllowedFailure);
        $returnValue = !empty($hhvmNonFailureAllowed)
            ? PackageVersion::HHVM_STATUS_SUPPORTED
            : PackageVersion::HHVM_STATUS_ALLOWED_FAILURE;

        return ($supports) ? $returnValue : PackageVersion::HHVM_STATUS_NONE;
    }

    private function isHHVMString($string) {
        return in_array(strtolower($string), $this->hhvmStrings);
    }
}
