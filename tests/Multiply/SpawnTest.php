<?php declare(strict_types=1);

namespace Qlimix\Tests\Process\Multiply;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Qlimix\Process\Control\ControlInterface;
use Qlimix\Process\Control\Exception\ControlException;
use Qlimix\Process\Multiply\Exception\SpawnException;
use Qlimix\Process\Multiply\Spawn;
use Qlimix\Process\Runtime\Registry\RegistryInterface;

final class SpawnTest extends TestCase
{
    private const MAX_PROCESSES = 3;
    private MockObject $control;

    private MockObject $registry;

    private Spawn $spawn;

    public function setUp(): void
    {
        $this->control = $this->createMock(ControlInterface::class);
        $this->registry = $this->createMock(RegistryInterface::class);

        $this->spawn = new Spawn(
            'bin/test',
            $this->control,
            $this->registry,
            self::MAX_PROCESSES
        );
    }

    public function testShouldSpawn(): void
    {
        $this->control->expects($this->exactly(self::MAX_PROCESSES))
            ->method('start');

        $this->spawn->spawn();
    }

    public function testShouldNotSpawnMoreThanMax(): void
    {
        $this->control->expects($this->exactly(self::MAX_PROCESSES))
            ->method('start');

        $this->registry->expects($this->once())
            ->method('count')
            ->willReturn(0);

        $this->spawn->spawn();
    }

    public function testShouldNotSpawnMoreIfMaxRunning(): void
    {
        $this->registry->expects($this->once())
            ->method('count')
            ->willReturn(self::MAX_PROCESSES);

        $this->control->expects($this->never())
            ->method('start');

        $this->spawn->spawn();
    }

    public function testShouldThrowOnStartException(): void
    {
        $this->registry->expects($this->once())
            ->method('count')
            ->willReturn(0);

        $this->control->expects($this->once())
            ->method('start')
            ->willThrowException(new ControlException());

        $this->expectException(SpawnException::class);
        $this->spawn->spawn();
    }
}
