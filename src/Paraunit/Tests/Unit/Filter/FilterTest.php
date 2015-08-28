<?php

namespace Paraunit\Tests\Unit\Filter;

use Paraunit\Filter\Filter;

class FilterTest extends \PHPUnit_Framework_TestCase
{
    const PHPUNIT_UTIL_XML_PROXY_CLASS = 'Paraunit\Proxy\PHPUnit_Util_XML_Proxy';
    const FILE_ITERATOR_FACADE_CLASS = '\File_Iterator_Facade';

    public function testFilterTestFiles_gets_only_requested_testsuite()
    {
        $configFile = 'stubbed_for_filter_test.xml';
        $testSuiteName = 'test_only_requested_testsuite';

        $utilXml = $this->prophesize(static::PHPUNIT_UTIL_XML_PROXY_CLASS);
        $utilXml->loadFile($configFile, false, true, true)
            ->willReturn($this->getStubbedXMLConf($configFile))
            ->shouldBeCalled();

        $fileIterator = $this->prophesize(static::FILE_ITERATOR_FACADE_CLASS);
        $fileIterator->getFilesAsArray('./only/selected/test/suite/', 'Test.php', '', array())
            ->willReturn(array('OnlyTestSuiteTest.php'))
            ->shouldBeCalledTimes(1);
        $fileIterator->getFilesAsArray('./other/test/suite/', 'Test.php', '', array())
            ->willReturn(array('OtherTest.php'))
            ->shouldNotBeCalled();

        $filter = new Filter($utilXml->reveal(), $fileIterator->reveal());

        $result = $filter->filterTestFiles($configFile, $testSuiteName);

        $this->assertCount(1, $result);
        $this->assertEquals(array('OnlyTestSuiteTest.php'), $result);
    }

    public function testFilterTestFiles_supports_suffix_attribute()
    {
        $configFile = 'stubbed_for_suffix_test.xml';

        $utilXml = $this->prophesize(static::PHPUNIT_UTIL_XML_PROXY_CLASS);
        $utilXml->loadFile($configFile, false, true, true)
            ->willReturn($this->getStubbedXMLConf($configFile))
            ->shouldBeCalled();

        $fileIterator = $this->prophesize(static::FILE_ITERATOR_FACADE_CLASS);
        $fileIterator->getFilesAsArray('./only/selected/test/suite/', 'TestSuffix.php', '', array())
            ->willReturn(array('OneTestSuffix.php'))
            ->shouldBeCalledTimes(1);
        $fileIterator->getFilesAsArray('./other/test/suite/', 'Test.php', '', array())
            ->willReturn(array('OtherTest.php'))
            ->shouldBeCalledTimes(1);

        $filter = new Filter($utilXml->reveal(), $fileIterator->reveal());

        $result = $filter->filterTestFiles($configFile);
        $this->assertEquals(array('OneTestSuffix.php', 'OtherTest.php'), $result);
    }

    public function testFilterTestFiles_supports_prefix_attribute()
    {
        $configFile = 'stubbed_for_prefix_test.xml';

        $utilXml = $this->prophesize(static::PHPUNIT_UTIL_XML_PROXY_CLASS);
        $utilXml->loadFile($configFile, false, true, true)
            ->willReturn($this->getStubbedXMLConf($configFile))
            ->shouldBeCalled();

        $fileIterator = $this->prophesize(static::FILE_ITERATOR_FACADE_CLASS);
        $fileIterator->getFilesAsArray('./only/selected/test/suite/', 'Test.php', 'TestPrefix', array())
            ->willReturn(array('TestPrefixOneTest.php'))
            ->shouldBeCalledTimes(1);
        $fileIterator->getFilesAsArray('./other/test/suite/', 'Test.php', '', array())
            ->willReturn(array('OtherTest.php'))
            ->shouldBeCalledTimes(1);

        $filter = new Filter($utilXml->reveal(), $fileIterator->reveal());

        $result = $filter->filterTestFiles($configFile);
        $this->assertEquals(array('TestPrefixOneTest.php', 'OtherTest.php'), $result);
    }

    public function testFilterTestFiles_supports_exclude_nodes()
    {
        $configFile = 'stubbed_for_node_exclude.xml';

        $utilXml = $this->prophesize(static::PHPUNIT_UTIL_XML_PROXY_CLASS);
        $utilXml->loadFile($configFile, false, true, true)
            ->willReturn($this->getStubbedXMLConf($configFile))
            ->shouldBeCalled();

        $excludeArray1 = array(
            '/path/to/exclude1',
            '/path/to/exclude2',
        );

        $excludeArray2 = array(
            '/path/to/exclude3',
            '/path/to/exclude4',
        );

        $fileIterator = $this->prophesize(static::FILE_ITERATOR_FACADE_CLASS);
        $fileIterator->getFilesAsArray('./only/selected/test/suite/', 'Test.php', 'TestPrefix', $excludeArray1)
            ->willReturn(array('TestPrefixOneTest.php'))
            ->shouldBeCalledTimes(1);
        $fileIterator->getFilesAsArray('./other/test/suite/', 'Test.php', '', $excludeArray2)
            ->willReturn(array('OtherTest.php'))
            ->shouldBeCalledTimes(1);

        $filter = new Filter($utilXml->reveal(), $fileIterator->reveal());

        $result = $filter->filterTestFiles($configFile);
        $this->assertEquals(array('TestPrefixOneTest.php', 'OtherTest.php'), $result);
    }

    public function testFilterTestFiles_avoids_duplicate_runs()
    {
        $configFile = 'stubbed_for_filter_test.xml';

        $utilXml = $this->prophesize(static::PHPUNIT_UTIL_XML_PROXY_CLASS);
        $utilXml->loadFile($configFile, false, true, true)
            ->willReturn($this->getStubbedXMLConf($configFile))
            ->shouldBeCalled();

        $fileIterator = $this->prophesize(static::FILE_ITERATOR_FACADE_CLASS);
        $fileIterator->getFilesAsArray('./only/selected/test/suite/', 'Test.php', '', array())
            ->willReturn(array('SameFile.php'))
            ->shouldBeCalledTimes(1);
        $fileIterator->getFilesAsArray('./other/test/suite/', 'Test.php', '', array())
            ->willReturn(array('SameFile.php'))
            ->shouldBeCalledTimes(1);

        $filter = new Filter($utilXml->reveal(), $fileIterator->reveal());

        $result = $filter->filterTestFiles($configFile);
        $this->assertCount(1, $result);
        $this->assertEquals(array('SameFile.php'), $result);
    }

    public function testFilterTestFiles_supports_file_nodes()
    {
        $configFile = 'stubbed_for_node_file.xml';

        $utilXml = $this->prophesize(static::PHPUNIT_UTIL_XML_PROXY_CLASS);
        $utilXml->loadFile($configFile, false, true, true)
            ->willReturn($this->getStubbedXMLConf($configFile))
            ->shouldBeCalled();

        $fileIterator = $this->prophesize(static::FILE_ITERATOR_FACADE_CLASS);
        $fileIterator->getFilesAsArray('./only/selected/test/suite/', 'Test.php', '', array())
            ->willReturn(array('TestPrefixOneTest.php'))
            ->shouldBeCalledTimes(1);
        $fileIterator->getFilesAsArray('./other/test/suite/', 'Test.php', '', array())
            ->willReturn(array('OtherTest.php'))
            ->shouldBeCalledTimes(1);

        $filter = new Filter($utilXml->reveal(), $fileIterator->reveal());

        $result = $filter->filterTestFiles($configFile);
        $this->assertEquals(array('TestPrefixOneTest.php', './this/file.php', './this/file2.php', 'OtherTest.php'), $result);
    }

    public function testFilterTestFiles_prepends_config_file_path()
    {
        $path = '../../Stub/StubbedXMLConfigs/';
        $configFile = $path.'stubbed_for_node_file.xml';

        $utilXml = $this->prophesize(static::PHPUNIT_UTIL_XML_PROXY_CLASS);
        $utilXml->loadFile($configFile, false, true, true)
            ->willReturn($this->getStubbedXMLConf($configFile))
            ->shouldBeCalled();

        $fileIterator = $this->prophesize(static::FILE_ITERATOR_FACADE_CLASS);
        $fileIterator->getFilesAsArray('./only/selected/test/suite/', 'Test.php', '', array())
            ->willReturn(array('TestPrefixOneTest.php'))
            ->shouldBeCalledTimes(1);
        $fileIterator->getFilesAsArray('./other/test/suite/', 'Test.php', '', array())
            ->willReturn(array('OtherTest.php'))
            ->shouldBeCalledTimes(1);

        $filter = new Filter($utilXml->reveal(), $fileIterator->reveal());

        $result = $filter->filterTestFiles($configFile);
        $this->assertEquals(array($path.'TestPrefixOneTest.php', $path.'./this/file.php', $path.'./this/file2.php', $path.'OtherTest.php'), $result);
    }

    /**
     * @param string $fileName
     *
     * @return string
     *
     * @throws \Exception
     */
    private function getStubbedXMLConf($fileName)
    {
        $filePath = realpath(__DIR__.'/../../Stub/StubbedXMLConfigs/'.$fileName);

        if (!file_exists($filePath)) {
            throw new \Exception('Stub XML config file missing: '.$filePath);
        }

        return \PHPUnit_Util_XML::loadFile($filePath, false, true, true);
    }
}
