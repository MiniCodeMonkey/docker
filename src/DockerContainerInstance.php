<?php

namespace Spatie\Docker;

use Spatie\Macroable\Macroable;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class DockerContainerInstance
{
    use Macroable;

    private DockerContainer $config;

    private string $dockerIdentifier;

    private string $name;

    public function __construct(
        DockerContainer $config,
        string $dockerIdentifier,
        string $name
    ) {
        $this->config = $config;

        $this->dockerIdentifier = $dockerIdentifier;

        $this->name = $name;
    }

    public function __destruct()
    {
        if ($this->config->stopOnDestruct) {
            $this->stop();
        }
    }

    public function stop(): Process
    {
        $fullCommand = $this->config->getBasicCommand('stop', $this->getShortDockerIdentifier());

        $process = Process::fromShellCommandline($fullCommand);

        $process->run();

        return $process;
    }


    public function delete(): Process
    {
        $fullCommand = $this->config->getBasicCommand('rm', $this->getShortDockerIdentifier());

        $process = Process::fromShellCommandline($fullCommand);

        $process->run();

        return $process;
    }

    public function start(): Process
    {
        $fullCommand = $this->config->getBasicCommand('start', $this->getShortDockerIdentifier());

        $process = Process::fromShellCommandline($fullCommand);

        $process->run();

        return $process;
    }

    public function startAndStreamOutput(callable $outputCallback, int $timeoutInSeconds = 3000): void
    {
        $fullCommand = $this->config->getBasicCommand('start', $this->getShortDockerIdentifier()) . ' --attach';

        Process::fromShellCommandline($fullCommand)
            ->setTimeout($timeoutInSeconds)
            ->run($outputCallback);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getConfig(): DockerContainer
    {
        return $this->config;
    }

    public function getDockerIdentifier(): string
    {
        return $this->dockerIdentifier;
    }

    public function getShortDockerIdentifier(): string
    {
        return substr($this->dockerIdentifier, 0, 12);
    }

    /**
     * @param string|array $command
     *
     * @return \Symfony\Component\Process\Process
     */
    public function execute($command): Process
    {
        if (is_array($command)) {
            $command = implode(';', $command);
        }

        $fullCommand = $this->config->getExecCommand($this->getShortDockerIdentifier(), $command);

        $process = Process::fromShellCommandline($fullCommand);

        $process->run();

        return $process;
    }

    public function addPublicKey(string $pathToPublicKey, string $pathToAuthorizedKeys = '/root/.ssh/authorized_keys'): self
    {
        $publicKeyContents = trim(file_get_contents($pathToPublicKey));

        $this->execute('echo \''.$publicKeyContents.'\' >> '.$pathToAuthorizedKeys);

        $this->execute("chmod 600 {$pathToAuthorizedKeys}");
        $this->execute("chown root:root {$pathToAuthorizedKeys}");

        return $this;
    }

    public function addFiles(string $fileOrDirectoryOnHost, string $pathInContainer): self
    {
        $fullCommand = $this->config->getCopyCommand(
            $fileOrDirectoryOnHost,
            $this->getShortDockerIdentifier() . ':' . $pathInContainer
        );

        $process = Process::fromShellCommandline($fullCommand);

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $this;
    }

    public function addFileFromString(string $filename, string $contents): self
    {
        $temporaryDir = sys_get_temp_dir() . '/' . uniqid();
        mkdir($temporaryDir);

        $temporaryFile = $temporaryDir . '/' . pathinfo($filename, PATHINFO_BASENAME);

        file_put_contents($temporaryFile, $contents);
        $this->addFiles($temporaryFile, pathinfo($filename, PATHINFO_DIRNAME));

        @unlink($temporaryFile);
        @rmdir($temporaryDir);

        return $this;
    }

    public function getFiles(string $pathInContainer, string $fileOrDirectoryOnHost): self
    {
        $fullCommand = $this->config->getCopyCommand(
            $this->getShortDockerIdentifier() . ':' . $pathInContainer,
            $fileOrDirectoryOnHost
        );

        $process = Process::fromShellCommandline($fullCommand);

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $this;
    }

    public function getFileAsString(string $filename): string
    {
        $destinationFilename = tempnam(sys_get_temp_dir(), __FUNCTION__);
        $this->getFiles($filename, $destinationFilename);

        $contents = file_get_contents($destinationFilename);
        @unlink($destinationFilename);

        return $contents;
    }

    public function inspect(): array
    {
        $fullCommand = $this->config->getBasicCommand('inspect', $this->getShortDockerIdentifier());

        $process = Process::fromShellCommandline($fullCommand);
        $process->run();

        $json = trim($process->getOutput());

        return json_decode($json, true);
    }

}
