<?php

namespace Supervisor;

use Laminas\XmlRpc\Client as RpcClient;

class Process
{
    /** @var string */
    protected $processName;

    /** @var string */
    protected $processGroup;

    /** @var RpcClient */
    protected $rpcClient;

    public function __construct(string $processName, string $processGroup, RpcClient $rpcClient)
    {
        $this->processName = $processName;
        $this->processGroup = $processGroup;
        $this->rpcClient = $rpcClient;
    }

    public function getName(): string
    {
        return $this->processName;
    }

    public function getGroup(): string
    {
        return $this->processGroup;
    }

    public function getProcessInfo(): array
    {
        return $this->rpcClient->call('supervisor.getProcessInfo', [$this->processGroup . ':' . $this->processName]);
    }

    public function startProcess(bool $wait = true): bool
    {
        return $this->rpcClient->call('supervisor.startProcess', [$this->processGroup . ':' . $this->processName, $wait]);
    }

    public function stopProcess(bool $wait = true): bool
    {
        return $this->rpcClient->call('supervisor.stopProcess', [$this->processGroup . ':' . $this->processName, $wait]);
    }

    public function startProcessGroup(bool $wait = true): bool
    {
        return $this->rpcClient->call('supervisor.startProcessGroup', [$this->processGroup, $wait]);
    }

    public function stopProcessGroup(bool $wait = true): bool
    {
        return $this->rpcClient->call('supervisor.stopProcessGroup', [$this->processGroup, $wait]);
    }

    public function sendProcessStdin(string $data): bool
    {
        return $this->rpcClient->call('supervisor.sendProcessStdin', [$this->processGroup . ':' . $this->processName, $data]);
    }

    /**
     * @todo
     */
    public function addProcessGroup()
    {
        throw new \Exception('Todo');
    }

    /**
     * @todo
     */
    public function removeProcessGroup()
    {
        throw new \Exception('Todo');
    }

    public function readProcessStdoutLog(int $offset, int $length): string
    {
        return $this->rpcClient->call('supervisor.readProcessStdoutLog', [$this->processGroup . ':' . $this->processName, $offset, $length]);
    }

    public function readProcessStderrLog(int $offset, int $length): string
    {
        return $this->rpcClient->call('supervisor.readProcessStderrLog', [$this->processGroup . ':' . $this->processName, $offset, $length]);
    }

    public function tailProcessStdoutLog(int $offset, int $length): array
    {
        return $this->rpcClient->call('supervisor.tailProcessStdoutLog', [$this->processGroup . ':' . $this->processName, $offset, $length]);
    }

    public function tailProcessStderrLog(int $offset, int $length): array
    {
        return $this->rpcClient->call('supervisor.tailProcessStderrLog', [$this->processGroup . ':' . $this->processName, $offset, $length]);
    }

    public function clearProcessLogs(): bool
    {
        return $this->rpcClient->call('supervisor.clearProcessLogs', [$this->processGroup . ':' . $this->processName]);
    }
}
