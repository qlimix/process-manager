<?php declare(strict_types=1);

namespace Qlimix\Tests\Process;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Qlimix\Process\Exception\ProcessException;
use Qlimix\Process\Multiply\SpawnInterface;
use Qlimix\Process\MultiplyProcessManager;
use Qlimix\Process\ProcessControlInterface;
use Qlimix\Process\ProcessInterface;
use Qlimix\Process\Result\ExitedProcess;
use Qlimix\Process\Runtime\RuntimeControlInterface;

final class MultiplyProcessManagerTest extends TestCase
{
    /** @var MockObject */
    private $processControl;

    /** @var MockObject */
    private $runtimeControl;

    /** @var MockObject */
    private $spawn;

    /** @var MultiplyProcessManager */
    private $processManager;

    public function setUp(): void
    {
        $this->processControl = $this->createMock(ProcessControlInterface::class);
        $this->runtimeControl = $this->createMock(RuntimeControlInterface::class);
        $this->spawn = $this->createMock(SpawnInterface::class);

        $this->processManager = new MultiplyProcessManager(
            $this->processControl,
            $this->runtimeControl,
            $this->spawn
        );
    }

    /**
     * @test
     */
    public function shouldInitialize(): void
    {
        $this->spawn->expects($this->once())
            ->method('spawn');

        $this->processManager->initialize();
    }

    /**
     * @test
     */
    public function shouldThrowOnInitializeException(): void
    {
        $this->spawn->expects($this->once())
            ->method('spawn')
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

        $this->spawn->expects($this->once())
            ->method('spawn');

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
