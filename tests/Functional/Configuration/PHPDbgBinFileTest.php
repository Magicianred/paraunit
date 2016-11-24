<?php


namespace Tests\Functional\Configuration;

use Paraunit\Configuration\PHPDbgBinFile;
use Tests\BaseFunctionalTestCase;

/**
 * Class PHPDbgBinFileTest
 * @package Tests\Functional\Configuration
 */
class PHPDbgBinFileTest extends BaseFunctionalTestCase
{
    public function testGetPhpDbgBin()
    {
        $bin = new PHPDbgBinFile();
        
        $this->assertStringEndsWith('phpdbg', $bin->getPhpDbgBin());
        $this->assertNotContains(' ', $bin->getPhpDbgBin());
        $this->assertNotContains("\n", $bin->getPhpDbgBin());
    }
}
