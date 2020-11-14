<?php declare(strict_types=1);

namespace Qlimix\Process\Manager\Multiply;

use Qlimix\Process\Manager\Multiply\Exception\SpawnException;

interface SpawnInterface
{
    /**
     * @throws SpawnException
     */
    public function spawn(): void;
}
