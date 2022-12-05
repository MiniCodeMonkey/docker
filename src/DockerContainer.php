<?php

namespace Spatie\Docker;

use Spatie\Docker\Exceptions\CouldNotCreateDockerContainerContainer;
use Spatie\Docker\Exceptions\CouldNotPullDockerImageContainer;
use Spatie\Docker\Exceptions\CouldNotStartDockerContainerContainer;
use Symfony\Component\Process\Process;

class DockerContainer extends DockerCommand
{
    public string $image = '';

    public string $name = '';

    public bool $daemonize = true;

    public bool $privileged = false;

    public string $shell = 'bash';

    public ?string $network = null;

    /** @var PortMapping[] */
    public array $portMappings = [];

    /** @var EnvironmentMapping[] */
    public array $environmentMappings = [];

    /** @var VolumeMapping[] */
    public array $volumeMappings = [];

    /** @var LabelMapping[] */
    public array $labelMappings = [];

    public bool $cleanUpAfterExit = true;

    public bool $stopOnDestruct = false;

    public string $command = '';

    public array $optionalArgs = [];

    public function __construct(string $image, string $name = '')
    {
        $this->image = $image;

        $this->name = $name;
    }

    public static function create(...$args): self
    {
        return new static(...$args);
    }

    public function image(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function daemonize(bool $daemonize = true): self
    {
        $this->daemonize = $daemonize;

        return $this;
    }

    public function privileged(bool $privileged = true): self
    {
        $this->privileged = $privileged;

        return $this;
    }

    public function shell(string $shell): self
    {
        $this->shell = $shell;

        return $this;
    }

    public function network(string $network): self
    {
        $this->network = $network;

        return $this;
    }

    public function doNotDaemonize(): self
    {
        $this->daemonize = false;

        return $this;
    }

    public function cleanUpAfterExit(bool $cleanUpAfterExit): self
    {
        $this->cleanUpAfterExit = $cleanUpAfterExit;

        return $this;
    }

    public function doNotCleanUpAfterExit(): self
    {
        $this->cleanUpAfterExit = false;

        return $this;
    }

    /**
     * @param int|string $portOnHost
     */
    public function mapPort($portOnHost, int $portOnDocker): self
    {
        $this->portMappings[] = new PortMapping($portOnHost, $portOnDocker);

        return $this;
    }

    public function setEnvironmentVariable(string $envName, string $envValue): self
    {
        $this->environmentMappings[] = new EnvironmentMapping($envName, $envValue);

        return $this;
    }

    public function setEnvironmentVariables(array $variables): self
    {
        foreach ($variables as $key => $val) {
            $this->setEnvironmentVariable($key, $val);
        }

        return $this;
    }

    public function setVolume(string $pathOnHost, string $pathOnDocker): self
    {
        $this->volumeMappings[] = new VolumeMapping($pathOnHost, $pathOnDocker);

        return $this;
    }

    public function setLabel(string $labelName, string $labelValue): self
    {
        $this->labelMappings[] = new LabelMapping($labelName, $labelValue);

        return $this;
    }

    public function setOptionalArgs(...$args): self
    {
        $this->optionalArgs = $args;

        return $this;
    }

    public function stopOnDestruct(bool $stopOnDestruct = true): self
    {
        $this->stopOnDestruct = $stopOnDestruct;

        return $this;
    }

    public function command(string $command): self
    {
        $this->command = $command;

        return $this;
    }

    public function getStartCommand(string $verb): string
    {
        $startCommand = [
            $this->getBaseCommand(),
            $verb,
            ...$this->getExtraOptions(),
            $this->image,
        ];

        if ($this->command !== '') {
            $startCommand[] = $this->command;
        }

        return implode(' ', $startCommand);
    }

    public function getBasicCommand(string $verb, string $dockerIdentifier): string
    {
        $stopCommand = [
            $this->getBaseCommand(),
            $verb,
            $dockerIdentifier,
        ];

        return implode(' ', $stopCommand);
    }

    public function getExecCommand(string $dockerIdentifier, string $command): string
    {
        $execCommand = [
            "echo \"{$command}\"",
            '|',
            $this->getBaseCommand(),
            'exec',
            '--interactive',
            $dockerIdentifier,
            $this->shell,
            '-',
        ];

        return implode(' ', $execCommand);
    }

    public function getCopyCommand(string $source, string $destination): string
    {
        $copyCommand = [
            $this->getBaseCommand(),
            'cp',
            $source,
            $destination
        ];

        return implode(' ', $copyCommand);
    }

    public function pullImage(): self
    {
        $pullCommand = [
            $this->getBaseCommand(),
            'pull',
            $this->image,
        ];

        $process = Process::fromShellCommandline(implode(' ', $pullCommand));

        $process->run();

        if (! $process->isSuccessful()) {
            throw CouldNotPullDockerImageContainer::processFailed($this, $process);
        }

        return $this;
    }

    public function startPaused(): DockerContainerInstance
    {
        $command = $this->getStartCommand('create');

        $process = Process::fromShellCommandline($command);

        $process->run();

        if (! $process->isSuccessful()) {
            throw CouldNotCreateDockerContainerContainer::processFailed($this, $process);
        }

        return $this->createContainerInstanceFromProcess($process);
    }

    public function start(): DockerContainerInstance
    {
        $command = $this->getStartCommand('start');

        $process = Process::fromShellCommandline($command);

        $process->run();

        if (! $process->isSuccessful()) {
            throw CouldNotStartDockerContainerContainer::processFailed($this, $process);
        }

        return $this->createContainerInstanceFromProcess($process);
    }

    protected function getExtraOptions(): array
    {
        $extraOptions = [];

        if ($this->optionalArgs) {
            $extraOptions[] = implode(' ', $this->optionalArgs);
        }

        if (count($this->portMappings)) {
            $extraOptions[] = implode(' ', $this->portMappings);
        }

        if (count($this->environmentMappings)) {
            $extraOptions[] = implode(' ', $this->environmentMappings);
        }

        if (count($this->volumeMappings)) {
            $extraOptions[] = implode(' ', $this->volumeMappings);
        }

        if (count($this->labelMappings)) {
            $extraOptions[] = implode(' ', $this->labelMappings);
        }

        if ($this->name !== '') {
            $extraOptions[] = "--name {$this->name}";
        }

        if ($this->daemonize) {
            $extraOptions[] = '-d';
        }

        if ($this->privileged) {
            $extraOptions[] = '--privileged';
        }

        if ($this->cleanUpAfterExit) {
            $extraOptions[] = '--rm';
        }

        if ($this->network) {
            $extraOptions[] = '--network ' . $this->network;
        }

        return $extraOptions;
    }

    private function createContainerInstanceFromProcess(Process $process): DockerContainerInstance
    {
        $dockerIdentifier = trim($process->getOutput());

        return new DockerContainerInstance(
            $this,
            $dockerIdentifier,
            $this->name,
        );
    }

}
