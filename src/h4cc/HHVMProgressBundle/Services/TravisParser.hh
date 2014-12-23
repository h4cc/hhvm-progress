<?hh // strict

namespace h4cc\HHVMProgressBundle\Services;

use h4cc\HHVMProgressBundle\HHVM;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Yaml\Yaml;

class TravisParser
{
    /** @var array available hhvm versions on travis */
    private array<string> $hhvmStrings = ['hhvm', 'hhvm-nightly'];
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getHHVMStatus(string $content) : int
    {
        if(!$content) {
            return HHVM::STATUS_NONE;
        }

        try {
            $data = Yaml::parse($content);
        }catch(\Exception $e) {
            $this->logger->info($e->getMessage());
            $this->logger->debug($e);

            return HHVM::STATUS_NONE;
        }

        return $this->parseHHVMStatus($data);
    }

    private function isHHVMString($string) {
        return in_array(strtolower($string), $this->hhvmStrings);
    }

    private function parseHHVMStatus(array $data)
    {
        if(isset($data['language']) && 'php' != $data['language']) {
            // This is NOT a PHP build, so return.
            return HHVM::STATUS_NONE;
        }

        if(!isset($data['php'])) {
            // No php versions are set in this travis file, wierd.
            return HHVM::STATUS_NONE;
        }

        $hhvmBuilds = [];
        $hhvmAllowedFailure = [];

        foreach($data['php'] as $phpVersion) {
            if($this->isHHVMString($phpVersion)) {
                $hhvmBuilds[] = $phpVersion;
            }
        }

        // Check allowed failure matrix.
        if($hhvmBuilds && isset($data['matrix']) && isset($data['matrix']['allow_failures'])) {
            foreach($data['matrix']['allow_failures'] as $af) {
                if(is_array($af) && isset($af['php'])) {
                    $af['php'] = (array)$af['php'];

                    foreach($af['php'] as $phpString) {
                        if($this->isHHVMString($phpString)) {
                            $hhvmAllowedFailure[] = $phpString;
                        }
                    }
                }
            }
        }

        // array with hhvm string of not allowed hhvm builds
        // for now if at least one hhvm string is not allowed to fail, we'll mark
        // the project as tested.
        $hhvmNonFailureAllowed = array_diff($hhvmBuilds, $hhvmAllowedFailure);
        $returnValue = !empty($hhvmNonFailureAllowed)
            ? HHVM::STATUS_SUPPORTED
            : HHVM::STATUS_ALLOWED_FAILURE;

        return ($hhvmBuilds) ? $returnValue : HHVM::STATUS_NONE;
    }
}
