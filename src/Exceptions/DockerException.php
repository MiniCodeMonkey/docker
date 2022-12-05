<?php

namespace Spatie\Docker\Exceptions;

use Exception;
use Spatie\Docker\DockerContainer;
use Symfony\Component\Process\Process;

abstract class DockerException extends Exception
{
    public static function processFailed(DockerContainer $container, Process $process)
    {
        return new static("Could not " . static::getActionDescription() . " {$container->image}`. Process output: `{$process->getErrorOutput()}`");
    }

    abstract static protected function getActionDescription(): string;
}
