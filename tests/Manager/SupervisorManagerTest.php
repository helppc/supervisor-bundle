<?php declare(strict_types=1);

namespace HelpPC\Bundle\SupervisorBundle\Tests\Manager;

use HelpPC\Bundle\SupervisorBundle\Manager\SupervisorManager;
use HelpPC\Bundle\SupervisorBundle\Tests\Fixtures\RecordingHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class SupervisorManagerTest extends TestCase
{
    public function testRequestTargetsConfiguredServerWithBasicAuth(): void
    {
        $http = new RecordingHttpClient(self::xmlRpcStringResponse('3.0'));
        $manager = $this->createManager($http, [
            'api' => [
                'scheme' => 'http',
                'host' => '127.0.0.1',
                'port' => 9001,
                'username' => 'user',
                'password' => 's3cret',
            ],
        ]);

        $manager->getSupervisorByKey('api')?->getAPIVersion();

        self::assertCount(1, $http->requests);
        $request = $http->requests[0];
        self::assertSame('http://127.0.0.1:9001/RPC2', (string) $request->getUri());
        self::assertSame('Basic ' . base64_encode('user:s3cret'), $request->getHeaderLine('Authorization'));
    }

    public function testNoAuthorizationHeaderWithoutCredentials(): void
    {
        $http = new RecordingHttpClient(self::xmlRpcStringResponse('3.0'));
        $manager = $this->createManager($http, [
            'api' => ['host' => 'supervisor.example.com', 'port' => 9002],
        ]);

        $manager->getSupervisorByKey('api')?->getAPIVersion();

        $request = $http->requests[0];
        self::assertSame('http://supervisor.example.com:9002/RPC2', (string) $request->getUri());
        self::assertFalse($request->hasHeader('Authorization'));
    }

    public function testIsProcessVisibleMatchesNameAndGroup(): void
    {
        $manager = $this->createManager(new RecordingHttpClient(), [
            'api' => [
                'host' => '127.0.0.1',
                'hidden_processes' => ['php-fpm', 'nginx', 'fatal_exit'],
            ],
        ]);

        self::assertTrue($manager->isProcessVisible('api', 'scheduler', 'scheduler'));
        // hidden by name
        self::assertFalse($manager->isProcessVisible('api', 'other-group', 'php-fpm'));
        // hidden by group (numprocs>1 style: name worker_00, group nginx)
        self::assertFalse($manager->isProcessVisible('api', 'nginx', 'nginx_00'));
        self::assertFalse($manager->isProcessVisible('api', 'fatal_exit', 'fatal_exit'));
        // unknown server key is never visible
        self::assertFalse($manager->isProcessVisible('unknown', 'scheduler', 'scheduler'));
    }

    public function testGetVisibleProcessesFiltersHiddenOnes(): void
    {
        $http = new RecordingHttpClient(self::xmlRpcAllProcessInfoResponse([
            ['name' => 'php-fpm', 'group' => 'php-fpm', 'state' => 20, 'statename' => 'RUNNING'],
            ['name' => 'nginx', 'group' => 'nginx', 'state' => 20, 'statename' => 'RUNNING'],
            ['name' => 'scheduler', 'group' => 'scheduler', 'state' => 0, 'statename' => 'STOPPED'],
            ['name' => 'fatal_exit', 'group' => 'fatal_exit', 'state' => 20, 'statename' => 'RUNNING'],
        ]));
        $manager = $this->createManager($http, [
            'api' => [
                'host' => '127.0.0.1',
                'hidden_processes' => ['php-fpm', 'nginx', 'fatal_exit'],
            ],
        ]);

        $visible = $manager->getVisibleProcesses('api');

        self::assertCount(1, $visible);
        self::assertSame('scheduler', $visible[0]->getName());
    }

    public function testGetVisibleProcessesForUnknownServerIsEmpty(): void
    {
        $manager = $this->createManager(new RecordingHttpClient(), [
            'api' => ['host' => '127.0.0.1'],
        ]);

        self::assertSame([], $manager->getVisibleProcesses('unknown'));
    }

    public function testLogTailBytesFallsBackToDefault(): void
    {
        $manager = $this->createManager(new RecordingHttpClient(), [
            'api' => ['host' => '127.0.0.1', 'log_tail_bytes' => 1024],
        ]);

        self::assertSame(1024, $manager->getLogTailBytes('api'));
        self::assertSame(16384, $manager->getLogTailBytes('unknown'));
    }

    /**
     * @param array<string, array<string, mixed>> $configuration
     */
    private function createManager(RecordingHttpClient $http, array $configuration): SupervisorManager
    {
        /** @phpstan-ignore argument.type */
        return new SupervisorManager($configuration, $http, new Psr17Factory());
    }

    private static function xmlRpcStringResponse(string $value): ResponseInterface
    {
        return self::xmlResponse(sprintf(
            '<?xml version="1.0"?><methodResponse><params><param><value><string>%s</string></value></param></params></methodResponse>',
            $value,
        ));
    }

    /**
     * @param list<array{name: string, group: string, state: int, statename: string}> $processes
     */
    private static function xmlRpcAllProcessInfoResponse(array $processes): ResponseInterface
    {
        $structs = '';
        foreach ($processes as $process) {
            $structs .= sprintf(
                '<value><struct>
                    <member><name>name</name><value><string>%s</string></value></member>
                    <member><name>group</name><value><string>%s</string></value></member>
                    <member><name>state</name><value><int>%d</int></value></member>
                    <member><name>statename</name><value><string>%s</string></value></member>
                    <member><name>description</name><value><string>test</string></value></member>
                </struct></value>',
                $process['name'],
                $process['group'],
                $process['state'],
                $process['statename'],
            );
        }

        return self::xmlResponse(sprintf(
            '<?xml version="1.0"?><methodResponse><params><param><value><array><data>%s</data></array></value></param></params></methodResponse>',
            $structs,
        ));
    }

    private static function xmlResponse(string $xml): ResponseInterface
    {
        $factory = new Psr17Factory();

        return $factory->createResponse()
            ->withHeader('Content-Type', 'text/xml')
            ->withBody($factory->createStream($xml));
    }
}
