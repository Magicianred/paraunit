<?php

namespace Tests\Unit\Parser;

use Paraunit\Parser\RetryParser;
use Paraunit\TestResult\TestResultFormat;
use Tests\BaseUnitTestCase;
use Tests\Stub\EntityManagerClosedTestStub;
use Tests\Stub\MySQLDeadLockTestStub;
use Tests\Stub\MySQLLockTimeoutTestStub;
use Tests\Stub\PHPUnitJSONLogOutput\JSONLogStub;
use Tests\Stub\SQLiteDeadLockTestStub;
use Tests\Stub\StubbedParaProcess;

/**
 * Class RetryParserTest
 * @package Tests\Unit
 */
class RetryParserTest extends BaseUnitTestCase
{
    /**
     * @dataProvider toBeRetriedTestsProvider
     */
    public function testParseAndSetRetry($testOutput)
    {
        $log = $this->getLogWithStatus('error', $testOutput);

        $process = new StubbedParaProcess();
        $parser = new RetryParser(new TestResultFormat('A', '', ''), 'error', 3);
        $result = $parser->parseLog($process, $log);

        $this->assertInstanceOf('Paraunit\TestResult\MuteTestResult', $result);
        $this->assertEquals('A', $result->getTestResultSymbol());
        $this->assertTrue($process->isToBeRetried(), 'Test should be marked as to be retried!');
        $this->assertCount(1, $process->getTestResults());
        $this->assertEquals(array($result), $process->getTestResults(), 'Process should include the result!');
    }

    /**
     * @dataProvider notToBeRetriedTestLogsProvider
     */
    public function testParseAndContinueWithNoRetry($jsonLogs)
    {
        $process = new StubbedParaProcess();
        $parser = new RetryParser(new TestResultFormat('A', '', ''), 'error', 3);

        $logs = json_decode($jsonLogs);
        foreach ($logs as $singlelog) {
            if ($singlelog->event == 'test') {
                $this->assertNull($parser->parseLog($process, $singlelog), 'Parsing should continue!');
                $this->assertFalse($process->isToBeRetried(), 'Test shouldn\'t be retried!');
                $this->assertEmpty($process->getTestResults());
            }
        }
    }

    public function testParseAndContinueWithNoRetryAfterLimit()
    {
        $process = new StubbedParaProcess();
        $log = $this->getLogWithStatus('error', EntityManagerClosedTestStub::OUTPUT);
        $process->increaseRetryCount();

        $this->assertEquals(1, $process->getRetryCount());

        $parser = new RetryParser(new TestResultFormat('A', '', ''), 'error', 0);

        $this->assertNull($parser->parseLog($process, $log), 'Parsing should continue!');
        $this->assertFalse($process->isToBeRetried(), 'Test shouldn\'t be retried!');
        $this->assertEmpty($process->getTestResults());
    }

    public function toBeRetriedTestsProvider()
    {
        return array(
            array(EntityManagerClosedTestStub::OUTPUT),
            array(MySQLDeadLockTestStub::OUTPUT),
            array(MySQLLockTimeoutTestStub::OUTPUT),
            array(SQLiteDeadLockTestStub::OUTPUT),
        );
    }

    public function notToBeRetriedTestLogsProvider()
    {
        return array(
            array(JSONLogStub::getCleanOutputFileContent(JSONLogStub::TWO_ERRORS_TWO_FAILURES)),
            array(JSONLogStub::getCleanOutputFileContent(JSONLogStub::ALL_GREEN)),
            array(JSONLogStub::getCleanOutputFileContent(JSONLogStub::FATAL_ERROR)),
            array(JSONLogStub::getCleanOutputFileContent(JSONLogStub::SEGFAULT)),
            array(JSONLogStub::getCleanOutputFileContent(JSONLogStub::ONE_ERROR)),
            array(JSONLogStub::getCleanOutputFileContent(JSONLogStub::ONE_INCOMPLETE)),
            array(JSONLogStub::getCleanOutputFileContent(JSONLogStub::ONE_RISKY)),
            array(JSONLogStub::getCleanOutputFileContent(JSONLogStub::ONE_SKIP)),
            array(JSONLogStub::getCleanOutputFileContent(JSONLogStub::ONE_WARNING)),
        );
    }
}
