<?php

namespace Spatie\Docker\Exceptions;

class CouldNotPullDockerImage extends DockerException
{
    protected static function getActionDescription(): string
    {
        return 'pull Docker image';
    }
}
