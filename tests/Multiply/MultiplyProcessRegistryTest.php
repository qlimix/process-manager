<?php declare(strict_types=1);

namespace Qlimix\Tests\Process\Multiply;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Qlimix\Process\Exception\ProcessException;
use Qlimix\Process\Multiply\MultiplyProcessRegistry;
use Qlimix\Process\ProcessControlInterface;
use Qlimix\Process\ProcessInterface;
use Qlimix\Process\Result\ExitedProcess;

final class MultiplyProcessRegistryTest extends TestCase
{
    /** @var MockObject */
    private $process;

    /** @var MockObject */
    private $processControl;

    /** @var MultiplyProcessRegistry */
    private $multiplyProcessRegistry;

    public function setUp(): void
    {
        $this->process = $this->createMock(ProcessInterface::class);
        $this->processControl = $this->createMock(ProcessControlInterface::class);

        $this->multiplyProcessRegistry = new MultiplyProcessRegistry(
            $this->process,
            $this->processControl,
            3
        );
    }

    /**
     * @test
     */
    public function shouldSpawn(): void
    {
        $this->processControl->expects($this->exactly(3))
            ->method('startProcess');

        $this->multiplyProcessRegistry->spawn();
        $this->multiplyProcessRegistry->spawn();
        $this->multiplyProcessRegistry->spawn();
    }

    /**
     * @test
     */
    public function shouldNotSpawnMoreThanMax(): void
    {
        $this->processControl->expects($this->exactly(3))
            ->method('startProcess');

        $this->multiplyProcessRegistry->spawn();
        $this->multiplyProcessRegistry->spawn();
        $this->multiplyProcessRegistry->spawn();
        $this->multiplyProcessRegistry->spawn();
    }

    /**
     * @test
     */
    public function shouldDespawn(): void
    {
        $this->processControl->expects($this->exactly(4))
            ->method('startProcess');

        $this->multiplyProcessRegistry->spawn();
        $this->multiplyProcessRegistry->spawn();
        $this->multiplyProcessRegistry->spawn();

        $this->multiplyProcessRegistry->despawned();

        $this->multiplyProcessRegistry->spawn();
    }

    /**
     * @test
     */
    public function shouldQuit(): void
    {
        $this->multiplyProcessRegistry->spawn();
        $this->multiplyProcessRegistry->spawn();
        $this->multiplyProcessRegistry->spawn();

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

        $this->multiplyProcessRegistry->quit();
    }

    /**
     * @test
     */
    public function shouldThrowOnQuitStopProcessesException(): void
    {
        $this->multiplyProcessRegistry->spawn();
        $this->multiplyProcessRegistry->spawn();
        $this->multiplyProcessRegistry->spawn();

        $this->processControl->expects($this->once())
            ->method('stopProcesses')
            ->willThrowException(new ProcessException());

        $this->expectException(ProcessException::class);

        $this->multiplyProcessRegistry->quit();
    }
}
