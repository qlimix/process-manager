<?php declare(strict_types=1);

namespace Qlimix\Tests\Process;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Qlimix\Process\Control\ControlInterface;
use Qlimix\Process\Control\Exception\ControlException;
use Qlimix\Process\Control\Status;
use Qlimix\Process\Manager\Exception\ManagerException;
use Qlimix\Process\Manager\MultipleManager;
use Qlimix\Process\Runtime\RuntimeControlInterface;

final class MultipleManagerTest extends TestCase
{
    private array $processes;

    private MockObject $control;

    private MockObject $runtimeControl;

    private MultipleManager $manager;

    public function setUp(): void
    {
        $this->control = $this->createMock(ControlInterface::class);
        $this->runtimeControl = $this->createMock(RuntimeControlInterface::class);

        $this->processes = [
            'bin/test',
            'bin/test',
            'bin/test',
        ];

        $this->manager = new MultipleManager(
            $this->processes,
            $this->control,
            $this->runtimeControl
        );
    }

    /**
     * @test
     */
    public function shouldInitialize(): void
    {
        $this->control->expects($this->once())
            ->method('startMultiple');

        $this->manager->initialize();
    }

    /**
     * @test
     */
    public function shouldThrowOnInitializeException(): void
    {
        $this->control->expects($this->once())
            ->method('startMultiple')
            ->willThrowException(new ManagerException());

        $this->expectException(ManagerException::class);

        $this->manager->initialize();
    }

    /**
     * @test
     */
    public function shouldMaintainOnExit(): void
    {
        $this->control->expects($this->once())
            ->method('status')
            ->willReturn(new Status(1, 'bin/test', true));

        $this->control->expects($this->once())
            ->method('start');

        $this->manager->maintain();
    }

    /**
     * @test
     */
    public function shouldMaintainOnFailedExit(): void
    {
        $this->control->expects($this->once())
            ->method('status')
            ->willReturn(new Status(1, 'bin/test', false));

        $this->runtimeControl->expects($this->once())
            ->method('quit');

        $this->manager->maintain();
    }

    /**
     * @test
     */
    public function shouldMaintainOnNoExit(): void
    {
        $this->control->expects($this->once())
            ->method('status')
            ->willReturn(null);

        $this->manager->maintain();
    }

    /**
     * @test
     */
    public function shouldStop(): void
    {
        $this->control->expects($this->once())
            ->method('stopAll');

        $this->manager->stop();
    }

    /**
     * @test
     */
    public function shouldStopOnException(): void
    {
        $this->control->expects($this->once())
            ->method('stopAll')
            ->willThrowException(New ManagerException());

        $this->manager->stop();
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

        $this->control->expects($this->once())
            ->method('start')
            ->willThrowException(new ControlException());

        $this->expectException(ManagerException::class);
        $this->manager->maintain();
    }
}
