<?php declare(strict_types=1);

namespace HelpPC\Bundle\SupervisorBundle\Tests\DependencyInjection;

use HelpPC\Bundle\SupervisorBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = $this->process([
            'default_environment' => 'prod',
            'servers' => [
                'prod' => [
                    'api' => ['host' => '127.0.0.1'],
                ],
            ],
        ]);

        $server = $config['servers']['prod']['api'];
        self::assertSame('http', $server['scheme']);
        self::assertSame(9001, $server['port']);
        self::assertNull($server['username']);
        self::assertNull($server['password']);
        self::assertSame([], $server['hidden_processes']);
        self::assertSame(16384, $server['log_tail_bytes']);
    }

    public function testFullServerConfiguration(): void
    {
        $config = $this->process([
            'default_environment' => 'prod',
            'servers' => [
                'prod' => [
                    'api' => [
                        'scheme' => 'https',
                        'host' => 'supervisor.example.com',
                        'port' => 9002,
                        'username' => 'user',
                        'password' => 's3cret',
                        'hidden_processes' => ['php-fpm', 'nginx'],
                        'log_tail_bytes' => 1024,
                    ],
                ],
            ],
        ]);

        $server = $config['servers']['prod']['api'];
        self::assertSame('https', $server['scheme']);
        self::assertSame(['php-fpm', 'nginx'], $server['hidden_processes']);
        self::assertSame(1024, $server['log_tail_bytes']);
    }

    public function testHostIsRequired(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process([
            'default_environment' => 'prod',
            'servers' => [
                'prod' => [
                    'api' => ['port' => 9001],
                ],
            ],
        ]);
    }

    public function testDefaultEnvironmentIsRequired(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process([
            'servers' => [
                'prod' => [
                    'api' => ['host' => '127.0.0.1'],
                ],
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function process(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }
}
