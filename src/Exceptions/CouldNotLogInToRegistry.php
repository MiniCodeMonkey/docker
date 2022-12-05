<?php

namespace Spatie\Docker\Exceptions;

use Exception;
use Symfony\Component\Process\Process;

class CouldNotLogInToRegistry extends Exception
{
    public static function processFailed(Process $process)
    {
        return new static("Could not log in to Docker registry. Process output: `{$process->getErrorOutput()}`");
    }
}
