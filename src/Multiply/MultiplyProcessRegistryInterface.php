<?php declare(strict_types=1);

namespace Qlimix\Process\Multiply;

use Qlimix\Process\Exception\ProcessException;

interface MultiplyProcessRegistryInterface
{
    /**
     * @throws ProcessException
     */
    public function spawn(): void;

    public function despawned(): void;

    public function quit(): void;
}
