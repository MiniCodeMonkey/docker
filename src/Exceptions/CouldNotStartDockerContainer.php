<?php

namespace Spatie\Docker\Exceptions;

class CouldNotStartDockerContainer extends DockerException
{
    static protected function getActionDescription(): string
    {
        return 'start Docker container';
    }
}
