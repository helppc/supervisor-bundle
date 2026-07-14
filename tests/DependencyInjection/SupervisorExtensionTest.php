<?php declare(strict_types=1);

namespace HelpPC\Bundle\SupervisorBundle\Tests\DependencyInjection;

use HelpPC\Bundle\SupervisorBundle\Controller\SupervisorController;
use HelpPC\Bundle\SupervisorBundle\DependencyInjection\SupervisorExtension;
use HelpPC\Bundle\SupervisorBundle\Manager\SupervisorManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SupervisorExtensionTest extends TestCase
{
    public function testAlias(): void
    {
        self::assertSame('helppc_supervisor', (new SupervisorExtension())->getAlias());
    }

    public function testLoadSetsServersParameterForDefaultEnvironment(): void
    {
        $container = new ContainerBuilder();
        (new SupervisorExtension())->load([[
            'default_environment' => 'prod',
            'servers' => [
                'prod' => ['api' => ['host' => '127.0.0.1']],
                'staging' => ['api' => ['host' => '10.0.0.1']],
            ],
        ]], $container);

        /** @var array<string, array<string, mixed>> $servers */
        $servers = $container->getParameter('helppc_supervisor.servers');
        self::assertArrayHasKey('api', $servers);
        self::assertSame('127.0.0.1', $servers['api']['host']);

        self::assertTrue($container->hasDefinition(SupervisorManager::class));
        self::assertTrue($container->hasDefinition(SupervisorController::class));
    }

    public function testLoadRejectsUnknownDefaultEnvironment(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/default_environment "missing" is not defined/');

        (new SupervisorExtension())->load([[
            'default_environment' => 'missing',
            'servers' => [
                'prod' => ['api' => ['host' => '127.0.0.1']],
            ],
        ]], new ContainerBuilder());
    }
}
