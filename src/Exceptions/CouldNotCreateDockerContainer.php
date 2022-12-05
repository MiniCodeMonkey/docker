<?php

namespace Spatie\Docker\Exceptions;

class CouldNotCreateDockerContainer extends DockerException
{
    protected static function getActionDescription(): string
    {
        return 'create Docker container';
    }
}
