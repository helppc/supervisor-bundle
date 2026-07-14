<?php declare(strict_types=1);

namespace HelpPC\Bundle\SupervisorBundle;

use HelpPC\Bundle\SupervisorBundle\DependencyInjection\SupervisorExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SupervisorBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        // Explicit so the extension may use the "helppc_supervisor" alias
        // instead of the auto-derived "supervisor".
        return $this->extension ??= new SupervisorExtension();
    }
}
