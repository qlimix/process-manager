<?php declare(strict_types=1);

namespace Qlimix\Tests\Process;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Qlimix\Process\Exception\ProcessException;
use Qlimix\Process\Multiple\MultipleProcessRegistryInterface;
use Qlimix\Process\MultipleProcessManager;
use Qlimix\Process\ProcessControlInterface;
use Qlimix\Process\Result\ExitedProcess;
use Qlimix\Process\Runtime\RuntimeControlInterface;

final class MultipleProcessManagerTest extends TestCase
{
    /** @var MockObject*/
    private $multipleProcessManager;

    /** @var MockObject*/
    private $processControl;

    /** @var MockObject*/
    private $runtimeControl;

    /** @var MultipleProcessManager */
    private $processManager;

    public function setUp(): void
    {
        $this->multipleProcessManager = $this->createMock(MultipleProcessRegistryInterface::class);
        $this->processControl = $this->createMock(ProcessControlInterface::class);
        $this->runtimeControl = $this->createMock(RuntimeControlInterface::class);

        $this->processManager = new MultipleProcessManager(
            $this->multipleProcessManager,
            $this->processControl,
            $this->runtimeControl
        );
    }

    /**
     * @test
     */
    public function shouldInitialize(): void
    {
        $this->multipleProcessManager->expects($this->once())
            ->method('initialize');

        $this->processManager->initialize();
    }

    /**
     * @test
     */
    public function shouldThrowOnInitializeException(): void
    {
        $this->multipleProcessManager->expects($this->once())
            ->method('initialize')
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
            ->willReturn(new ExitedProcess(1, true));

        $this->multipleProcessManager->expects($this->once())
            ->method('removePid');

        $this->multipleProcessManager->expects($this->once())
            ->method('restartProcess');

        $this->processManager->maintain();
    }

    /**
     * @test
     */
    public function shouldMaintainOnFailedExit(): void
    {
        $this->processControl->expects($this->once())
            ->method('status')
            ->willReturn(new ExitedProcess(1, false));

        $this->multipleProcessManager->expects($this->once())
            ->method('removePid');

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
        $this->multipleProcessManager->expects($this->once())
            ->method('quit');

        $this->processManager->stop();
    }

    /**
     * @test
     */
    public function shouldStopOnException(): void
    {
        $this->multipleProcessManager->expects($this->once())
            ->method('quit')
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
