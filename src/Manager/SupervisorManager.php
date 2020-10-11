<?php declare(strict_types=1);

namespace HelpPC\Bundle\SupervisorBundle\Manager;

use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Supervisor\Supervisor;

class SupervisorManager
{
    /**
     * @var array<string,Supervisor>|Supervisor[]
     */
    private array $supervisors = [];

    /**
     * SupervisorManager constructor.
     * @param mixed[] $supervisorsConfiguration
     * @param HttpClient $httpClient
     * @param MessageFactory $factory
     */
    public function __construct(array $supervisorsConfiguration, HttpClient $httpClient, MessageFactory $factory)
    {
        foreach ($supervisorsConfiguration as $serverName => $configuration) {
            $client = new \fXmlRpc\Client(
                sprintf('%s://%s:%d/RPC2', $configuration['scheme'], $configuration['host'], $configuration['port']),
                new \fXmlRpc\Transport\HttpAdapterTransport(
                    $factory,
                    $httpClient
                )
            );
            $supervisor = new Supervisor($client);
            $this->supervisors[$serverName] = $supervisor;
        }
    }

    /**
     * Get all supervisors
     *
     * @return Supervisor[]
     */
    public function getSupervisors()
    {
        return $this->supervisors;
    }

    /**
     * Get Supervisor by identification
     *
     * @param string $serverName
     *
     * @return Supervisor|null
     */
    public function getSupervisorByKey(string $serverName): ?Supervisor
    {
        if (isset($this->supervisors[$serverName])) {
            return $this->supervisors[$serverName];
        }

        return null;
    }
}