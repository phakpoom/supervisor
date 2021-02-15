<?php

namespace YZ\SupervisorBundle\Manager;

use Supervisor\Supervisor;

class GroupRestrictedSupervisor extends Supervisor
{
    /** @var array */
    protected $groups;

    public function __construct(string $name, string $ipAddress, ?string $username = null, ?string $password = null, ?string $port = null, array $groups = [])
    {
        $this->groups = array_filter($groups);

        parent::__construct($name, $ipAddress, $username, $password, $port);
    }

    public function getProcesses(array $groups = []): array
    {
        return parent::getProcesses(empty($groups) ? $this->groups : $groups);
    }

    public function startAllProcesses(bool $wait = true): array
    {
        if (empty($this->groups)) {
            return parent::startAllProcesses($wait);
        }

        $results = [];

        foreach ($this->groups as $group) {
            $results = array_merge($results, parent::startProcessGroup($group, $wait));
        }

        return $results;
    }

    public function stopAllProcesses(bool $wait = true): array
    {
        if (empty($this->groups)) {
            return parent::stopAllProcesses($wait);
        }

        $results = [];

        foreach ($this->groups as $group) {
            $results = array_merge($results, parent::stopProcessGroup($group, $wait));
        }

        return $results;
    }
}
