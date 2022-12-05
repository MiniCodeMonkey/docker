<?php

namespace Spatie\Docker\Exceptions;

class CouldNotStartDockerContainerContainer extends DockerContainerException
{
    static protected function getActionDescription(): string
    {
        return 'start Docker container';
    }
}
