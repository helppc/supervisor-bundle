<?php declare(strict_types=1);

use HelpPC\Bundle\SupervisorBundle\Controller\SupervisorController;
use HelpPC\Bundle\SupervisorBundle\Manager\SupervisorManager;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Contracts\Translation\TranslatorInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    // Bundle-private PSR-17/PSR-18 stack so no app-level wiring is required.
    $services->set('helppc_supervisor.psr17_factory', Psr17Factory::class);
    $services->set('helppc_supervisor.psr18_client', Psr18Client::class);

    $services->set(SupervisorManager::class)
        ->args([
            param('helppc_supervisor.servers'),
            service('helppc_supervisor.psr18_client'),
            service('helppc_supervisor.psr17_factory'),
        ]);

    $services->set(SupervisorController::class)
        ->public()
        ->args([
            service(SupervisorManager::class),
            service(TranslatorInterface::class),
        ])
        ->call('setContainer', [service(Psr\Container\ContainerInterface::class)])
        ->tag('controller.service_arguments')
        ->tag('container.service_subscriber');
};
