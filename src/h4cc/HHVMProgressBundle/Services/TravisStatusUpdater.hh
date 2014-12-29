<?hh // strict

namespace h4cc\HHVMProgressBundle\Services;

use h4cc\HHVMProgressBundle\HHVM;
use h4cc\HHVMProgressBundle\Entity\TravisContent;
use h4cc\HHVMProgressBundle\Entity\TravisContentRepository;
use h4cc\HHVMProgressBundle\Services\TravisParser;
use Psr\Log\LoggerInterface;

class TravisStatusUpdater
{
    private LoggerInterface $logger;
    private TravisContentRepository $travisRepo;
    private TravisParser $travisParser;

    public function __construct(TravisContentRepository $travisRepo, TravisParser $travisParser, LoggerInterface $logger) {
        $this->travisRepo = $travisRepo;
        $this->travisParser = $travisParser;
        $this->logger = $logger;
    }

    public function updateAllStatus() {
        $contents = $this->travisRepo->all();

        foreach($contents as $content) {
            $this->logger->debug('Checking travis content '.$content->getId());
            $this->updateContent($content);
        }
    }

    private function updateContent(TravisContent $content) {
        if(!$content->getContent()) {
            // No content, no parsing.
            return;
        }

        $parsedHhvmStatus = $this->travisParser->getHHVMStatus($content->getContent());

        if($parsedHhvmStatus != $content->getHhvmStatus()) {
            $this->logger->debug('Updating travis content '.$content->getId().' HHVM status from '.$content->getHhvmStatus().' to '.$parsedHhvmStatus);

            $content->setHhvmStatus($parsedHhvmStatus);

            $this->travisRepo->save($content);
        }
    }
}
