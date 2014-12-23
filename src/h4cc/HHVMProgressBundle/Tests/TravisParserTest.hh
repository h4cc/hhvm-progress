<?hh //strict

namespace h4cc\HHVMProgressBundle\Tests;

use h4cc\HHVMProgressBundle\HHVM;
use h4cc\HHVMProgressBundle\Services\TravisParser;

class TravisParserTest extends \PHPUnit_Framework_TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new TravisParser();
    }

    /**
     * @test
     * @dataProvider yamlFiles
     */
    public function getHHVMStatus($file, $expectedStatus)
    {
        $hhvmStatus = $this->parser->getHHVMStatus(file_get_contents($file));

        $this->assertEquals($expectedStatus, $hhvmStatus);
    }

    public function yamlFiles()
    {
        return [
            [__DIR__.'/Fixtures/empty.yml', HHVM::STATUS_NONE],
            [__DIR__.'/Fixtures/not_php.yml', HHVM::STATUS_NONE],
            [__DIR__.'/Fixtures/php_no_hhvm.yml', HHVM::STATUS_NONE],
            [__DIR__.'/Fixtures/php_hhvm_allowedfailure.yml', HHVM::STATUS_ALLOWED_FAILURE],
            [__DIR__.'/Fixtures/php_hhvm.yml', HHVM::STATUS_SUPPORTED],
        ];
    }
}
