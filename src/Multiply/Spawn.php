<?php declare(strict_types=1);

namespace Qlimix\Process\Multiply;

use Qlimix\Process\Control\ControlInterface;
use Qlimix\Process\Multiply\Exception\SpawnException;
use Qlimix\Process\Runtime\Registry\RegistryInterface;
use Throwable;

final class Spawn implements SpawnInterface
{
    private string $process;

    private ControlInterface $control;

    private RegistryInterface $registry;

    private int $maxProcesses;

    public function __construct(
        string $process,
        ControlInterface $control,
        RegistryInterface $registry,
        int $maxProcesses
    ) {
        $this->process = $process;
        $this->control = $control;
        $this->registry = $registry;
        $this->maxProcesses = $maxProcesses;
    }

    /**
     * @inheritDoc
     */
    public function spawn(): void
    {
        $count = $this->registry->count();
        if ($this->maxProcesses === $count) {
            return;
        }

        try {
            $toSpawn = $this->maxProcesses - $count;
            for ($i = 0; $i < $toSpawn; $i++) {
                $this->control->start($this->process);
            }
        } catch (Throwable $exception) {
            throw new SpawnException('Couldn\'t start process', 0, $exception);
        }
    }
}
