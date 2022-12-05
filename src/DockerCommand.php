<?php

namespace Spatie\Docker;

use Spatie\Macroable\Macroable;

abstract class DockerCommand
{
    use Macroable;

    public string $remoteHost = '';

    public function remoteHost(string $remoteHost): self
    {
        $this->remoteHost = $remoteHost;

        return $this;
    }

    public function getBaseCommand(): string
    {
        $baseCommand = [
            'docker',
            ...$this->getExtraDockerOptions(),
        ];

        return implode(' ', $baseCommand);
    }

    protected function getExtraDockerOptions(): array
    {
        $extraDockerOptions = [];

        if ($this->remoteHost !== '') {
            $extraDockerOptions[] = "-H {$this->remoteHost}";
        }

        return $extraDockerOptions;
    }

}
