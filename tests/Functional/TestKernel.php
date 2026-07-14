<?php declare(strict_types=1);

namespace HelpPC\Bundle\SupervisorBundle\Tests\Functional;

use HelpPC\Bundle\SupervisorBundle\SupervisorBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new TwigBundle(),
            new SupervisorBundle(),
        ];
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/supervisor-bundle-tests/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/supervisor-bundle-tests/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test-secret',
            'test' => true,
            'session' => [
                'storage_factory_id' => 'session.storage.factory.mock_file',
            ],
            'router' => ['utf8' => true],
        ]);

        $container->extension('twig', [
            'strict_variables' => true,
        ]);

        $container->extension('helppc_supervisor', [
            'default_environment' => 'test',
            'servers' => [
                'test' => [
                    // Points at a port nothing listens on: index must degrade to
                    // the offline state, actions to an error flash + redirect.
                    'api' => [
                        'host' => '127.0.0.1',
                        'port' => 64999,
                        'username' => 'user',
                        'password' => 's3cret',
                        'hidden_processes' => ['php-fpm', 'nginx', 'fatal_exit'],
                    ],
                ],
            ],
        ]);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@SupervisorBundle/config/routes.php')->prefix('/supervisor');
    }
}
