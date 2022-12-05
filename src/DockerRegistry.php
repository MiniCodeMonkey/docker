<?php

namespace Spatie\Docker;

use Spatie\Docker\Exceptions\CouldNotLogInToRegistry;
use Symfony\Component\Process\Process;

class DockerRegistry extends DockerCommand
{
    public function login(?string $server, string $username, string $password): void
    {
        $command = [
            $this->getBaseCommand(),
            'login',
            $server,
            $username ? ('--username ' . $username) : null,
            $password ? ('$--password ' . $password) : null,
        ];

        $process = Process::fromShellCommandline(implode(' ', array_filter($command)));

        $process->run();

        if (! $process->isSuccessful()) {
            throw CouldNotLogInToRegistry::processFailed($process);
        }
    }
}
