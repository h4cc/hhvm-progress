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

    private $toUpdate = [];

    public function __construct(TravisContentRepository $travisRepo, TravisParser $travisParser, LoggerInterface $logger)
    {
        $this->travisRepo = $travisRepo;
        $this->travisParser = $travisParser;
        $this->logger = $logger;
    }

    public function updateAllStatus()
    {
        $this->toUpdate = [];
        $contents = $this->travisRepo->all();

        foreach($contents as $content) {
            $this->logger->debug('Checking travis content '.$content->getId());
            $this->updateContent($content);
            $this->updateStatusByFileExists($content);
        }

        foreach($this->toUpdate as $content) {
            $this->travisRepo->save($content);
        }
    }

    private function updateContent(TravisContent $content)
    {
        if(!$content->getContent()) {
            // No content, no parsing.
            return;
        }

        $parsedHhvmStatus = $this->travisParser->getHHVMStatus($content->getContent());

        if($parsedHhvmStatus != $content->getHhvmStatus()) {
            $this->logger->debug('Updating travis content '.$content->getId().' HHVM status from '.$content->getHhvmStatus().' to '.$parsedHhvmStatus);

            $content->setHhvmStatus($parsedHhvmStatus);

            $this->toUpdate[] = $content;
        }
    }

    private function updateStatusByFileExists(TravisContent $content)
    {
        if(!$content->getFileExists() && HHVM::STATUS_UNKNOWN != $content->getHhvmStatus()) {
            $this->logger->debug('Updating travis content '.$content->getId().' because file does not exist HHVM status from '.$content->getHhvmStatus().' to '.HHVM::STATUS_UNKNOWN);

            $content->setHhvmStatus(HHVM::STATUS_UNKNOWN);

            $this->toUpdate[] = $content;
        }
    }
}
