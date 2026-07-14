<?php declare(strict_types=1);

use HelpPC\Bundle\SupervisorBundle\Controller\SupervisorController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('supervisor', '/')
        ->controller([SupervisorController::class, 'index'])
        ->methods(['GET']);

    $routes->add('supervisor.process.start', '/{key}/process/{group}/{name}/start')
        ->controller([SupervisorController::class, 'startProcess'])
        ->methods(['POST']);

    $routes->add('supervisor.process.stop', '/{key}/process/{group}/{name}/stop')
        ->controller([SupervisorController::class, 'stopProcess'])
        ->methods(['POST']);

    $routes->add('supervisor.process.restart', '/{key}/process/{group}/{name}/restart')
        ->controller([SupervisorController::class, 'restartProcess'])
        ->methods(['POST']);

    $routes->add('supervisor.process.log', '/{key}/process/{group}/{name}/log/stdout')
        ->controller([SupervisorController::class, 'showProcessLog'])
        ->methods(['GET']);

    $routes->add('supervisor.process.error', '/{key}/process/{group}/{name}/log/stderr')
        ->controller([SupervisorController::class, 'showProcessLogErr'])
        ->methods(['GET']);

    $routes->add('supervisor.process.info', '/{key}/process/{group}/{name}/info')
        ->controller([SupervisorController::class, 'showProcessInfo'])
        ->methods(['GET']);

    $routes->add('supervisor.log', '/{key}/log')
        ->controller([SupervisorController::class, 'showSupervisorLog'])
        ->methods(['GET']);
};
