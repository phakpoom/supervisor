<?php

namespace Supervisor;

use Laminas\XmlRpc\Client as RpcClient;
use Laminas\Http\Client as HttpClient;

/**
 * Supervisor
 */
class Supervisor
{
    /**
     * @var RpcClient
     */
    protected $rpcClient;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $key;

    public function __construct(string $name, string $ipAddress, ?string $username = null, ?string $password = null, ?string $port = null)
    {
        $this->name = $name;

        $this->rpcClient = new RpcClient('http://' . $ipAddress . ':' . $port . '/RPC2/');

        if ($username !== null && $password !== null) {
            $this->rpcClient->getHttpClient()->setAuth($username, $password, HttpClient::AUTH_BASIC);
        }

        $this->createKey($ipAddress, $username, $password, $port);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getName(): string
    {
        return $this->name;
    }

    private function createKey(string $ipAdress, string $port, ?string $username = null, ?string $password = null)
    {
        $this->key = hash('md5', serialize(array(
            $ipAdress,
            $port,
            $username,
            $password,
        )));
    }

    public function getProcessByNameAndGroup(string $name, string $group): Process
    {
        return new Process($name, $group, $this->rpcClient);
    }

    public function getProcesses(array $groups = []): array
    {
        $processes = [];

        $result = $this->rpcClient->call('supervisor.getAllProcessInfo');
        foreach ($result as $cnt => $process) {
            // Skip process when process group not listed in $groups
            if (!empty($groups) && !in_array($process['group'], $groups)) {
                continue;
            }

            $processes[$cnt] = new Process($process['name'], $process['group'], $this->rpcClient);
        }

        return $processes;
    }

    public function checkConnection(): bool
    {
        try {
            $this->getState();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getAPIVersion(): string
    {
        return $this->rpcClient->call('supervisor.getAPIVersion');
    }

    public function getSupervisorVersion(): string
    {
        return $this->rpcClient->call('supervisor.getSupervisorVersion');
    }

    public function getIdentification(): string
    {
        return $this->rpcClient->call('supervisor.getIdentification');
    }

    public function getState(): array
    {
        return $this->rpcClient->call('supervisor.getState');
    }

    public function getPID(): int
    {
        return $this->rpcClient->call('supervisor.getPID');
    }

    public function readLog(int $offset, int $length): string
    {
        return $this->rpcClient->call('supervisor.readLog', array($offset, $length));
    }

    public function clearLog(): bool
    {
        return $this->rpcClient->call('supervisor.clearLog');
    }

    public function startAllProcesses(bool $wait = true): array
    {
        return $this->rpcClient->call('supervisor.startAllProcesses', [$wait]);
    }

    public function startProcessGroup(string $group, bool $wait = true): array
    {
        return $this->rpcClient->call('supervisor.startProcessGroup', [$group, $wait]);
    }

    public function stopAllProcesses(bool $wait = true): array
    {
        return $this->rpcClient->call('supervisor.stopAllProcesses', [$wait]);
    }

    public function stopProcessGroup(string $group, bool $wait = true): array
    {
        return $this->rpcClient->call('supervisor.stopProcessGroup', [$group, $wait]);
    }

    public function sendRemoteCommEvent($type, $data): bool
    {
        return $this->rpcClient->call('supervisor.sendRemoteCommEvent', [$type, $data]);
    }

    public function clearAllProcessLogs(): bool
    {
        return $this->rpcClient->call('supervisor.clearAllProcessLogs');
    }
}
