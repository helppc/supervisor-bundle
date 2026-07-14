<?php declare(strict_types=1);

namespace HelpPC\Bundle\SupervisorBundle\Manager;

use fXmlRpc\Client;
use fXmlRpc\Transport\PsrTransport;
use HelpPC\Bundle\SupervisorBundle\Http\BasicAuthClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Supervisor\ProcessInterface;
use Supervisor\Supervisor;

class SupervisorManager
{
    private const DEFAULT_LOG_TAIL_BYTES = 16384;

    /** @var array<string, Supervisor> */
    private array $supervisors = [];

    /** @var array<string, list<string>> */
    private array $hiddenProcesses = [];

    /** @var array<string, int> */
    private array $logTailBytes = [];

    /**
     * @param array<string, array{scheme?: string, host: string, port?: int|string, username?: string|null, password?: string|null, hidden_processes?: array<string>, log_tail_bytes?: int}> $supervisorsConfiguration
     */
    public function __construct(
        array $supervisorsConfiguration,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
    ) {
        foreach ($supervisorsConfiguration as $serverName => $configuration) {
            $serverName = (string) $serverName;

            $client = $httpClient;
            $username = $configuration['username'] ?? null;
            $password = $configuration['password'] ?? null;
            if ($username !== null && $username !== '' && $password !== null) {
                $client = new BasicAuthClient($client, $username, $password);
            }

            $rpcClient = new Client(
                sprintf(
                    '%s://%s:%d/RPC2',
                    $configuration['scheme'] ?? 'http',
                    $configuration['host'],
                    (int) ($configuration['port'] ?? 9001),
                ),
                new PsrTransport($requestFactory, $client),
            );

            $this->supervisors[$serverName] = new Supervisor($rpcClient);
            $this->hiddenProcesses[$serverName] = array_values(array_map(strval(...), $configuration['hidden_processes'] ?? []));
            $this->logTailBytes[$serverName] = (int) ($configuration['log_tail_bytes'] ?? self::DEFAULT_LOG_TAIL_BYTES);
        }
    }

    /**
     * @return array<string, Supervisor>
     */
    public function getSupervisors(): array
    {
        return $this->supervisors;
    }

    public function getSupervisorByKey(string $serverName): ?Supervisor
    {
        return $this->supervisors[$serverName] ?? null;
    }

    /**
     * A process is visible (listable and controllable) unless its name or its
     * group is listed in the server's `hidden_processes`. Matching the group as
     * well covers numprocs>1 programs (name `worker_00` in group `worker`) and
     * event listeners (group == name).
     */
    public function isProcessVisible(string $serverName, string $group, string $name): bool
    {
        if (!isset($this->supervisors[$serverName])) {
            return false;
        }

        $hidden = $this->hiddenProcesses[$serverName] ?? [];

        return !\in_array($name, $hidden, true) && !\in_array($group, $hidden, true);
    }

    /**
     * @return list<ProcessInterface>
     */
    public function getVisibleProcesses(string $serverName): array
    {
        $supervisor = $this->getSupervisorByKey($serverName);
        if ($supervisor === null) {
            return [];
        }

        $visible = [];
        foreach ($supervisor->getAllProcesses() as $process) {
            $payload = $process->getPayload();
            if ($this->isProcessVisible($serverName, (string) ($payload['group'] ?? ''), $process->getName())) {
                $visible[] = $process;
            }
        }

        return $visible;
    }

    public function getLogTailBytes(string $serverName): int
    {
        return $this->logTailBytes[$serverName] ?? self::DEFAULT_LOG_TAIL_BYTES;
    }
}
