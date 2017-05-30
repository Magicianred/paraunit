<?php

namespace Tests\Unit\Runner;

use Paraunit\Filter\Filter;
use Paraunit\Lifecycle\EngineEvent;
use Paraunit\Lifecycle\ProcessEvent;
use Paraunit\Process\ProcessBuilderFactory;
use Paraunit\Runner\Pipeline;
use Paraunit\Runner\PipelineCollection;
use Paraunit\Runner\Runner;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Process\ProcessBuilder;
use Tests\BaseUnitTestCase;
use Tests\Stub\StubbedParaunitProcess;

class RunnerTest extends BaseUnitTestCase
{
    public function testRunEmptyTestSuite()
    {
        $filter = $this->prophesize(Filter::class);
        $filter->filterTestFiles()
            ->willReturn([]);
        $pipelineCollection = $this->prophesize(PipelineCollection::class);
        $pipelineCollection->hasEmptySlots()
            ->willReturn(true);
        $pipelineCollection->checkRunningState()
            ->willReturn(false);

        $runner = new Runner(
            $this->mockEventDispatcher(),
            $this->mockProcessBuilderFactory(),
            $filter->reveal(),
            $pipelineCollection->reveal()
        );

        $this->assertSame(0, $runner->run());
    }

    public function testRunWithSomeGreenTests()
    {
        $filter = $this->prophesize(Filter::class);
        $filter->filterTestFiles()
            ->willReturn([
                'Test1.php',
                'Test2.php',
            ]);
        $pipelineCollection = $this->prophesize(PipelineCollection::class);
        $pipelineCollection->hasEmptySlots()
            ->willReturn(true);
        $pipelineCollection->checkRunningState()
            ->willReturn(false);
        $pipelineCollection->push(Argument::cetera())
            ->shouldBeCalledTimes(2)
            ->willReturn($this->prophesize(Pipeline::class)->reveal());

        $runner = new Runner(
            $this->mockEventDispatcher(),
            $this->mockProcessBuilderFactory(),
            $filter->reveal(),
            $pipelineCollection->reveal()
        );

        $this->assertSame(0, $runner->run());
    }

    public function testOnProcessParsingCompletedWithFailedProcess()
    {
        $process = new StubbedParaunitProcess();
        $process->setIsToBeRetried(false);
        $process->setExitCode(1);

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::cetera())
            ->shouldNotBeCalled();
        
        $filter = $this->prophesize(Filter::class);
        $filter->filterTestFiles()
            ->willReturn([]);
        $pipelineCollection = $this->prophesize(PipelineCollection::class);
        $pipelineCollection->push($process)
            ->shouldNotBeCalled();

        $runner = new Runner(
            $eventDispatcher->reveal(),
            $this->mockProcessBuilderFactory(),
            $filter->reveal(),
            $pipelineCollection->reveal()
        );

        $runner->onProcessParsingCompleted(new ProcessEvent($process));

        $this->assertAttributeNotSame(0, 'exitCode', $runner);
    }

    public function testOnProcessParsingCompletedWithRetriableProcess()
    {
        $process = new StubbedParaunitProcess();
        $process->setIsToBeRetried(true);
        $process->setExitCode(1);

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(ProcessEvent::PROCESS_TO_BE_RETRIED, Argument::type(ProcessEvent::class))
            ->shouldBeCalledTimes(1);
        
        $filter = $this->prophesize(Filter::class);
        $filter->filterTestFiles()
            ->willReturn([]);
        $pipelineCollection = $this->prophesize(PipelineCollection::class);
        $pipelineCollection->push($process)
            ->shouldBeCalledTimes(1);

        $runner = new Runner(
            $eventDispatcher->reveal(),
            $this->mockProcessBuilderFactory(),
            $filter->reveal(),
            $pipelineCollection->reveal()
        );

        $runner->onProcessParsingCompleted(new ProcessEvent($process));

        $this->assertAttributeSame(0, 'exitCode', $runner);
    }

    private function mockEventDispatcher(): EventDispatcherInterface
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(EngineEvent::BEFORE_START, Argument::cetera())
            ->shouldBeCalledTimes(1)
            ->will(function () use ($eventDispatcher) {
                $eventDispatcher->dispatch(EngineEvent::START, Argument::cetera())
                    ->shouldBeCalledTimes(1)
                    ->will(function () use ($eventDispatcher) {
                        $eventDispatcher->dispatch(EngineEvent::END, Argument::cetera())
                            ->shouldBeCalledTimes(1);
                    });
            });

        return $eventDispatcher->reveal();
    }

    private function mockProcessBuilderFactory(): ProcessBuilderFactory
    {
        $processBuilderFactory = $this->prophesize(ProcessBuilderFactory::class);
        $processBuilderFactory->create(Argument::containingString('.php'))
            ->willReturn($this->mockProcessBuilder());

        return $processBuilderFactory->reveal();
    }

    private function mockProcessBuilder(): ProcessBuilder
    {
        $processBuilder = $this->prophesize(ProcessBuilder::class);
        
        return $processBuilder->reveal();
    }
}
