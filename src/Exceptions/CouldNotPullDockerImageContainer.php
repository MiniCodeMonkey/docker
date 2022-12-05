<?php

namespace Spatie\Docker\Exceptions;

class CouldNotPullDockerImageContainer extends DockerContainerException
{
    protected static function getActionDescription(): string
    {
        return 'pull Docker image';
    }
}
