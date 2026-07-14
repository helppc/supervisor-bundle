<?php declare(strict_types=1);

namespace HelpPC\Bundle\SupervisorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class SupervisorExtension extends Extension
{
    public function getAlias(): string
    {
        return 'helppc_supervisor';
    }

    /**
     * @param mixed[] $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (!isset($config['servers'][$config['default_environment']])) {
            throw new InvalidConfigurationException(sprintf(
                'helppc_supervisor.default_environment "%s" is not defined under helppc_supervisor.servers (available: "%s").',
                $config['default_environment'],
                implode('", "', array_keys($config['servers'])),
            ));
        }

        $container->setParameter('helppc_supervisor.servers', $config['servers'][$config['default_environment']]);

        $loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config'));
        $loader->load('services.php');
    }
}
