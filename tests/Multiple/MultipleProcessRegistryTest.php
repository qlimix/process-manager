<?php declare(strict_types=1);

namespace Qlimix\Tests\Process\Multiple;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Qlimix\Process\Exception\ProcessException;
use Qlimix\Process\Multiple\MultipleProcessRegistry;
use Qlimix\Process\ProcessControlInterface;
use Qlimix\Process\ProcessInterface;
use Qlimix\Process\Result\ExitedProcess;
use Qlimix\Process\Runtime\RuntimeControlInterface;

final class MultipleProcessRegistryTest extends TestCase
{
    /** @var MockObject[] */
    private $processes;

    /** @var MockObject */
    private $processControl;

    /** @var MockObject */
    private $runtimeControl;

    /** @var MultipleProcessRegistry */
    private $multipleProcessRegistry;

    public function setUp(): void
    {
        $this->processes = [
            $this->createMock(ProcessInterface::class),
            $this->createMock(ProcessInterface::class),
            $this->createMock(ProcessInterface::class),
        ];

        $this->processControl = $this->createMock(ProcessControlInterface::class);
        $this->runtimeControl = $this->createMock(RuntimeControlInterface::class);

        $this->multipleProcessRegistry = new MultipleProcessRegistry(
            $this->processes,
            $this->processControl,
            $this->runtimeControl
        );
    }

    /**
     * @test
     */
    public function shouldInitialize(): void
    {
        $this->processControl->expects($this->exactly(3))
            ->method('startProcess');

        $this->multipleProcessRegistry->initialize();
    }

    /**
     * @test
     */
    public function shouldThrowOnInitializeStartProcessException(): void
    {
        $this->processControl->expects($this->once())
            ->method('startProcess')
            ->willThrowException(new ProcessException());

        $this->expectException(ProcessException::class);

        $this->multipleProcessRegistry->initialize();
    }

    /**
     * @test
     */
    public function shouldRemovePid(): void
    {
        $this->processControl->expects($this->exactly(3))
            ->method('startProcess')
            ->willReturnOnConsecutiveCalls(1, 2, 3);

        $this->multipleProcessRegistry->initialize();

        $index = $this->multipleProcessRegistry->removePid(1);

        $this->assertSame($index, 2);
    }

    /**
     * @test
     */
    public function shouldThrowOnRemovePidException(): void
    {
        $this->expectException(ProcessException::class);

        $this->multipleProcessRegistry->removePid(1);
    }

    /**
     * @test
     */
    public function shouldRestartProcess(): void
    {
        $this->runtimeControl->expects($this->once())
            ->method('abort')
            ->willReturn(false);

        $this->processControl->expects($this->once())
            ->method('startProcess');

        $this->multipleProcessRegistry->restartProcess(1);
    }

    /**
     * @test
     */
    public function shouldThrowOnRestartProcessInvalidProcessIndex(): void
    {
        $this->runtimeControl->expects($this->once())
            ->method('abort')
            ->willReturn(false);

        $this->expectException(ProcessException::class);

        $this->multipleProcessRegistry->restartProcess(8);
    }

    /**
     * @test
     */
    public function shouldQuit(): void
    {
        $this->processControl->expects($this->exactly(3))
            ->method('startProcess')
            ->willReturnOnConsecutiveCalls(1, 2, 3);

        $this->multipleProcessRegistry->initialize();

        $this->processControl->expects($this->once())
            ->method('stopProcesses');

        $this->processControl->expects($this->exactly(4))
            ->method('status')
            ->willReturnOnConsecutiveCalls(
                new ExitedProcess(0, true),
                new ExitedProcess(1, true),
                null,
                new ExitedProcess(2, true)
            );

        $this->multipleProcessRegistry->quit();
    }

    /**
     * @test
     */
    public function shouldThrowOnQuitStopProcessesException(): void
    {
        $this->processControl->expects($this->once())
            ->method('stopProcesses')
            ->willThrowException(new ProcessException());

        $this->expectException(ProcessException::class);

        $this->multipleProcessRegistry->quit();
    }
}
