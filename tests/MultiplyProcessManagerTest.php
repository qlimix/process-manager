<?php declare(strict_types=1);

namespace Qlimix\Tests\Process;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Qlimix\Process\Exception\ProcessException;
use Qlimix\Process\Multiply\MultiplyProcessRegistryInterface;
use Qlimix\Process\MultiplyProcessManager;
use Qlimix\Process\ProcessControlInterface;
use Qlimix\Process\Result\ExitedProcess;
use Qlimix\Process\Runtime\RuntimeControlInterface;

final class MultiplyProcessManagerTest extends TestCase
{
    /** @var MockObject */
    private $multiplyProcessManager;

    /** @var MockObject */
    private $processControl;

    /** @var MockObject */
    private $runtimeControl;

    /** @var MultiplyProcessManager */
    private $processManager;

    public function setUp(): void
    {
        $this->multiplyProcessManager = $this->createMock(MultiplyProcessRegistryInterface::class);
        $this->processControl = $this->createMock(ProcessControlInterface::class);
        $this->runtimeControl = $this->createMock(RuntimeControlInterface::class);

        $this->processManager = new MultiplyProcessManager(
            $this->multiplyProcessManager,
            $this->processControl,
            $this->runtimeControl
        );
    }

    /**
     * @test
     */
    public function shouldInitialize(): void
    {
        $this->multiplyProcessManager->expects($this->once())
            ->method('spawn');

        $this->processManager->initialize();
    }

    /**
     * @test
     */
    public function shouldThrowOnInitializeException(): void
    {
        $this->multiplyProcessManager->expects($this->once())
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
            ->willReturn(new ExitedProcess(1, true));

        $this->multiplyProcessManager->expects($this->once())
            ->method('despawned');

        $this->multiplyProcessManager->expects($this->once())
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
            ->willReturn(new ExitedProcess(1, false));

        $this->multiplyProcessManager->expects($this->once())
            ->method('despawned');

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
        $this->multiplyProcessManager->expects($this->once())
            ->method('quit');

        $this->processManager->stop();
    }

    /**
     * @test
     */
    public function shouldStopOnException(): void
    {
        $this->multiplyProcessManager->expects($this->once())
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
