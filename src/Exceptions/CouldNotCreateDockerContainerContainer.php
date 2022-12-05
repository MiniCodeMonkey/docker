<?php

namespace Spatie\Docker\Exceptions;

class CouldNotCreateDockerContainerContainer extends DockerContainerException
{
    protected static function getActionDescription(): string
    {
        return 'create Docker container';
    }
}
