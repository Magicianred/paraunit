<?php

namespace Paraunit\Tests\Unit;

use Paraunit\Parser\TestResultsParser;
use Paraunit\Tests\Stub\StubbedParaProcess;
use Paraunit\Tests\StubbedPHPUnitBaseTestCase;

class TestResultsParserTest extends StubbedPHPUnitBaseTestCase
{
    /**
     * @dataProvider processProvider
     */
    public function testParseAndContinue(StubbedParaProcess $process, $expectedResult)
    {
        $parser = new TestResultsParser();

        $this->assertTrue($parser->parsingFoundResult($process));
        $this->assertEquals($expectedResult, $process->getTestResults());
    }

    public function processProvider()
    {
        return array(
            array($this->getTestWithSingleWarning(), str_split('.....W...')),
            array($this->getTestWithSingleError(), str_split('....E....')),
            array($this->getTestWith2Errors2Failures(), str_split('FF..E...E')),
            array($this->getTestWithAllGreen(), str_split('.........')),
            array($this->getTestWithAllGreen5(), str_split('....................................................')),
            array($this->getTestWithFatalError(), array()),
            array($this->getTestWithSegFault(), array()),
        );
    }

    public function testParseAndContinueWithVeryLongOutput()
    {
        $process = $this->getTestWithVeryLongOutput();

        $parser = new TestResultsParser();

        $this->assertTrue($parser->parsingFoundResult($process));
        $this->assertEquals(str_repeat('.', 77), implode($process->getTestResults()));
    }
}
