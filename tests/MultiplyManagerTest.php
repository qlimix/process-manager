<?php declare(strict_types=1);

namespace Qlimix\Tests\Process;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Qlimix\Process\Control\ControlInterface;
use Qlimix\Process\Control\Exception\ControlException;
use Qlimix\Process\Control\Status;
use Qlimix\Process\Manager\Exception\ManagerException;
use Qlimix\Process\Multiply\Exception\SpawnException;
use Qlimix\Process\Multiply\SpawnInterface;
use Qlimix\Process\MultiplyManager;
use Qlimix\Process\Runtime\RuntimeControlInterface;

final class MultiplyManagerTest extends TestCase
{
    private MockObject $control;

    private MockObject $runtimeControl;

    private MockObject $spawn;

    private MultiplyManager $manager;

    public function setUp(): void
    {
        $this->control = $this->createMock(ControlInterface::class);
        $this->runtimeControl = $this->createMock(RuntimeControlInterface::class);
        $this->spawn = $this->createMock(SpawnInterface::class);

        $this->manager = new MultiplyManager(
            $this->control,
            $this->runtimeControl,
            $this->spawn
        );
    }

    public function testShouldInitialize(): void
    {
        $this->spawn->expects($this->once())
            ->method('spawn');

        $this->manager->initialize();
    }

    public function testShouldThrowOnInitializeException(): void
    {
        $this->spawn->expects($this->once())
            ->method('spawn')
            ->willThrowException(new ManagerException());

        $this->expectException(ManagerException::class);

        $this->manager->initialize();
    }

    public function testShouldMaintainOnExit(): void
    {
        $this->control->expects($this->once())
            ->method('status')
            ->willReturn(new Status(1, 'bin/test', true));

        $this->spawn->expects($this->once())
            ->method('spawn');

        $this->manager->maintain();
    }

    public function testShouldMaintainOnFailedExit(): void
    {
        $this->control->expects($this->once())
            ->method('status')
            ->willReturn(new Status(1, 'bin/test', false));

        $this->runtimeControl->expects($this->once())
            ->method('quit');

        $this->manager->maintain();
    }

    public function testShouldMaintainOnNoExit(): void
    {
        $this->control->expects($this->once())
            ->method('status')
            ->willReturn(null);

        $this->manager->maintain();
    }

    public function testShouldStop(): void
    {
        $this->control->expects($this->once())
            ->method('stopAll');

        $this->manager->stop();
    }

    public function testShouldStopOnException(): void
    {
        $this->control->expects($this->once())
            ->method('stopAll')
            ->willThrowException(New ManagerException());

        $this->manager->stop();
    }

    public function testShouldContinue(): void
    {
        $this->runtimeControl->expects($this->once())
            ->method('tick');

        $this->runtimeControl->expects($this->once())
            ->method('abort');

        $this->manager->continue();
    }

    public function testShouldThrowOnStatusException(): void
    {
        $this->control->expects($this->once())
            ->method('status')
            ->willThrowException(new ControlException());

        $this->expectException(ManagerException::class);
        $this->manager->maintain();
    }

    public function testShouldThrowOnStartException(): void
    {
        $this->control->expects($this->once())
            ->method('status')
            ->willReturn(new Status(1, 'bin/test', true));

        $this->spawn->expects($this->once())
            ->method('spawn')
            ->willThrowException(new SpawnException());

        $this->expectException(ManagerException::class);
        $this->manager->maintain();
    }
}
