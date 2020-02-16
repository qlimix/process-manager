<?php declare(strict_types=1);

namespace Qlimix\Tests\Process;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Qlimix\Process\Manager\ManagerInterface;
use Qlimix\Process\ProcessRunner;
use Qlimix\Process\Runner\Exception\RunnerException;
use Qlimix\Process\Runtime\RuntimeControlInterface;
use Qlimix\Time\TimeLapseInterface;

final class ProcessRunnerTest extends TestCase
{
    private MockObject $processManager;

    private MockObject$runtimeControl;

    private MockObject $timeLapse;

    private ProcessRunner $processRunner;

    public function setUp(): void
    {
        $this->processManager = $this->createMock(ManagerInterface::class);
        $this->runtimeControl = $this->createMock(RuntimeControlInterface::class);
        $this->timeLapse = $this->createMock(TimeLapseInterface::class);

        $this->processRunner = new ProcessRunner(
            $this->processManager,
            $this->runtimeControl,
            $this->timeLapse
        );
    }

    /**
     * @test
     */
    public function shouldRun(): void
    {
        $this->processManager->expects($this->once())
            ->method('initialize');

        $this->processManager->expects($this->exactly(3))
            ->method('maintain');

        $this->processManager->expects($this->exactly(3))
            ->method('continue')
            ->willReturnOnConsecutiveCalls(true, true, false);

        $this->processManager->expects($this->once())
            ->method('stop');

        $this->timeLapse->expects($this->exactly(2))
            ->method('lapse');

        $this->processRunner->run();
    }

    /**
     * @test
     */
    public function shouldThrowOnInitializeException(): void
    {
        $this->processManager->expects($this->once())
            ->method('initialize')
            ->willThrowException(new RunnerException());

        $this->expectException(RunnerException::class);

        $this->processRunner->run();
    }

    /**
     * @test
     */
    public function shouldThrowOnMaintainException(): void
    {
        $this->processManager->expects($this->once())
            ->method('initialize');

        $this->processManager->expects($this->once())
            ->method('maintain')
            ->willThrowException(new RunnerException());

        $this->runtimeControl->expects($this->once())
            ->method('quit');

        $this->processManager->expects($this->once())
            ->method('continue')
            ->willReturn(false);

        $this->processManager->expects($this->once())
            ->method('stop');

        $this->processRunner->run();
    }
}
