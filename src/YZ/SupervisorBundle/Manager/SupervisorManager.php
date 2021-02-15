<?php

namespace YZ\SupervisorBundle\Manager;

use Supervisor\Supervisor;


class SupervisorManager
{
    /** @var array */
    private $supervisors = [];

    public function __construct(array $supervisorsConfiguration)
    {
        foreach ($supervisorsConfiguration as $serverName => $configuration) {
            $supervisor = new GroupRestrictedSupervisor(
                $serverName,
                $configuration['host'],
                $configuration['username'],
                $configuration['password'],
                $configuration['port'],
                $configuration['groups']
            );
            $this->supervisors[$supervisor->getKey()] = $supervisor;
        }
    }

    /**
     * @return array|Supervisor[]
     */
    public function getSupervisors(): array
    {
        return $this->supervisors;
    }

    public function getSupervisorByKey(string $key): ?Supervisor
    {
        if (isset($this->supervisors[$key])) {
            return $this->supervisors[$key];
        }

        return null;
    }
}
