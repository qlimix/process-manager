<?php declare(strict_types=1);

namespace Qlimix\Tests\Process;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Qlimix\Process\Exception\ProcessRunnerException;
use Qlimix\Process\Output\OutputInterface;
use Qlimix\Process\ProcessManagerInterface;
use Qlimix\Process\ProcessRunner;
use Qlimix\Process\Runtime\RuntimeControlInterface;
use Qlimix\Time\TimeLapseInterface;

final class ProcessRunnerTest extends TestCase
{
    /** @var MockObject */
    private $processManager;

    /** @var MockObject */
    private $runtimeControl;

    /** @var MockObject */
    private $timeLapse;

    /** @var MockObject */
    private $output;

    /** @var ProcessRunner */
    private $processRunner;

    public function setUp(): void
    {
        $this->processManager = $this->createMock(ProcessManagerInterface::class);
        $this->runtimeControl = $this->createMock(RuntimeControlInterface::class);
        $this->timeLapse = $this->createMock(TimeLapseInterface::class);
        $this->output = $this->createMock(OutputInterface::class);

        $this->processRunner = new ProcessRunner(
            $this->processManager,
            $this->runtimeControl,
            $this->timeLapse,
            $this->output
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
            ->willThrowException(new ProcessRunnerException());

        $this->expectException(ProcessRunnerException::class);

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
            ->willThrowException(new ProcessRunnerException());

        $this->output->expects($this->once())
            ->method('write');

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
