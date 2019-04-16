<?php declare(strict_types=1);

namespace Qlimix\Tests\Process;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Qlimix\Process\Exception\ProcessException;
use Qlimix\Process\MultipleProcessManager;
use Qlimix\Process\ProcessControlInterface;
use Qlimix\Process\ProcessInterface;
use Qlimix\Process\Result\ExitedProcess;
use Qlimix\Process\Runtime\RuntimeControlInterface;

final class MultipleProcessManagerTest extends TestCase
{
    /** @var MockObject[]*/
    private $processes;

    /** @var MockObject*/
    private $processControl;

    /** @var MockObject*/
    private $runtimeControl;

    /** @var MultipleProcessManager */
    private $processManager;

    public function setUp(): void
    {
        $this->processControl = $this->createMock(ProcessControlInterface::class);
        $this->runtimeControl = $this->createMock(RuntimeControlInterface::class);

        $this->processes = [
            $this->createMock(ProcessInterface::class),
            $this->createMock(ProcessInterface::class),
            $this->createMock(ProcessInterface::class),
        ];

        $this->processManager = new MultipleProcessManager(
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

        $this->processManager->initialize();
    }

    /**
     * @test
     */
    public function shouldThrowOnInitializeException(): void
    {
        $this->processControl->expects($this->once())
            ->method('startProcess')
            ->willThrowException(new ProcessException());

        $this->expectException(ProcessException::class);

        $this->processManager->initialize();
    }

    /**
     * @test
     */
    public function shouldMaintainOnExit(): void
    {
        $this->processControl->expects($this->once())
            ->method('status')
            ->willReturn(new ExitedProcess($this->createMock(ProcessInterface::class), true));

        $this->processControl->expects($this->once())
            ->method('startProcess');

        $this->processManager->maintain();
    }

    /**
     * @test
     */
    public function shouldMaintainOnFailedExit(): void
    {
        $this->processControl->expects($this->once())
            ->method('status')
            ->willReturn(new ExitedProcess($this->createMock(ProcessInterface::class), false));

        $this->runtimeControl->expects($this->once())
            ->method('quit');

        $this->processManager->maintain();
    }

    /**
     * @test
     */
    public function shouldMaintainOnNoExit(): void
    {
        $this->processControl->expects($this->once())
            ->method('status')
            ->willReturn(null);

        $this->processManager->maintain();
    }

    /**
     * @test
     */
    public function shouldStop(): void
    {
        $this->processControl->expects($this->once())
            ->method('stopProcesses');

        $this->processManager->stop();
    }

    /**
     * @test
     */
    public function shouldStopOnException(): void
    {
        $this->processControl->expects($this->once())
            ->method('stopProcesses')
            ->willThrowException(New ProcessException());

        $this->processManager->stop();
    }

    /**
     * @test
     */
    public function shouldContinue(): void
    {
        $this->runtimeControl->expects($this->once())
            ->method('tick');

        $this->runtimeControl->expects($this->once())
            ->method('abort');

        $this->processManager->continue();
    }
}
