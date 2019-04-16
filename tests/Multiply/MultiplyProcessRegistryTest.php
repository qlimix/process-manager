<?php declare(strict_types=1);

namespace Qlimix\Tests\Process\Multiply;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Qlimix\Process\Multiply\Spawn;
use Qlimix\Process\ProcessControlInterface;
use Qlimix\Process\ProcessInterface;
use Qlimix\Process\Registry\RegistryInterface;

final class MultiplyProcessRegistryTest extends TestCase
{
    /** @var MockObject */
    private $process;

    /** @var MockObject */
    private $processControl;

    /** @var MockObject */
    private $registry;

    /** @var Spawn */
    private $multiplyProcessRegistry;

    public function setUp(): void
    {
        $this->process = $this->createMock(ProcessInterface::class);
        $this->processControl = $this->createMock(ProcessControlInterface::class);
        $this->registry = $this->createMock(RegistryInterface::class);

        $this->multiplyProcessRegistry = new Spawn(
            $this->process,
            $this->processControl,
            $this->registry,
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

        $this->registry->expects($this->exactly(4))
            ->method('count')
            ->willReturnOnConsecutiveCalls(0, 1, 2, 3);

        $this->multiplyProcessRegistry->spawn();
        $this->multiplyProcessRegistry->spawn();
        $this->multiplyProcessRegistry->spawn();
        $this->multiplyProcessRegistry->spawn();
    }
}
