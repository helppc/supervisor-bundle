<?php declare(strict_types=1);

namespace HelpPC\Bundle\SupervisorBundle\Controller;

use HelpPC\Bundle\SupervisorBundle\Manager\SupervisorManager;
use Supervisor\Exception\SupervisorException;
use Supervisor\Supervisor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class SupervisorController extends AbstractController
{
    public function __construct(
        private readonly SupervisorManager $supervisorManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function index(): Response
    {
        $servers = [];
        foreach ($this->supervisorManager->getSupervisors() as $key => $supervisor) {
            $connected = $supervisor->isConnected();

            $server = [
                'connected' => $connected,
                'processes' => [],
                'supervisorVersion' => null,
                'apiVersion' => null,
            ];

            if ($connected) {
                try {
                    $server['processes'] = $this->supervisorManager->getVisibleProcesses($key);
                    $server['supervisorVersion'] = $supervisor->getSupervisorVersion();
                    $server['apiVersion'] = $supervisor->getAPIVersion();
                } catch (\Exception) {
                    $server['connected'] = false;
                }
            }

            $servers[$key] = $server;
        }

        return $this->render('@Supervisor/Supervisor/list.html.twig', [
            'servers' => $servers,
        ]);
    }

    public function startProcess(string $key, string $group, string $name): Response
    {
        $supervisor = $this->getSupervisorForVisibleProcess($key, $group, $name);

        try {
            $success = $supervisor->startProcess($this->getProcessIdentification($group, $name), true) === true;
        } catch (\Exception) {
            $success = false;
        }
        $this->flashResult($success, 'process.start');

        return $this->redirectToRoute('supervisor');
    }

    public function stopProcess(string $key, string $group, string $name): Response
    {
        $supervisor = $this->getSupervisorForVisibleProcess($key, $group, $name);

        try {
            $success = $supervisor->stopProcess($this->getProcessIdentification($group, $name), true) === true;
        } catch (\Exception) {
            $success = false;
        }
        $this->flashResult($success, 'process.stop');

        return $this->redirectToRoute('supervisor');
    }

    public function restartProcess(string $key, string $group, string $name): Response
    {
        $supervisor = $this->getSupervisorForVisibleProcess($key, $group, $name);
        $identification = $this->getProcessIdentification($group, $name);

        try {
            try {
                $supervisor->stopProcess($identification, true);
            } catch (SupervisorException) {
                // Not running (stopped/exited/fatal) — restart degrades to a plain start.
            }
            $success = $supervisor->startProcess($identification, true) === true;
        } catch (\Exception) {
            $success = false;
        }
        $this->flashResult($success, 'process.restart');

        return $this->redirectToRoute('supervisor');
    }

    public function showProcessLog(string $key, string $group, string $name): Response
    {
        return $this->renderProcessLogTail($key, $group, $name, stderr: false);
    }

    public function showProcessLogErr(string $key, string $group, string $name): Response
    {
        return $this->renderProcessLogTail($key, $group, $name, stderr: true);
    }

    public function showSupervisorLog(string $key): Response
    {
        $supervisor = $this->getSupervisor($key);

        try {
            $log = (string) $supervisor->readLog(-$this->supervisorManager->getLogTailBytes($key), 0);
        } catch (\Exception) {
            $log = '';
        }

        return $this->render('@Supervisor/Supervisor/showLog.html.twig', [
            'title' => sprintf('supervisord (%s)', $key),
            'log' => $log,
        ]);
    }

    public function showProcessInfo(string $key, string $group, string $name): Response
    {
        $supervisor = $this->getSupervisorForVisibleProcess($key, $group, $name);

        try {
            $informations = $supervisor->getProcessInfo($this->getProcessIdentification($group, $name));
        } catch (\Exception) {
            $informations = [];
        }

        return $this->render('@Supervisor/Supervisor/showInformations.html.twig', [
            'title' => $this->getProcessIdentification($group, $name),
            'informations' => $informations,
        ]);
    }

    private function renderProcessLogTail(string $key, string $group, string $name, bool $stderr): Response
    {
        $supervisor = $this->getSupervisorForVisibleProcess($key, $group, $name);
        $identification = $this->getProcessIdentification($group, $name);
        $bytes = $this->supervisorManager->getLogTailBytes($key);

        try {
            $result = $stderr
                ? $supervisor->tailProcessStderrLog($identification, 0, $bytes)
                : $supervisor->tailProcessStdoutLog($identification, 0, $bytes);
            $log = (string) ($result[0] ?? '');
        } catch (\Exception) {
            $log = '';
        }

        return $this->render('@Supervisor/Supervisor/showLog.html.twig', [
            'title' => sprintf('%s — %s', $identification, $stderr ? 'stderr' : 'stdout'),
            'log' => $log,
        ]);
    }

    private function getSupervisor(string $key): Supervisor
    {
        return $this->supervisorManager->getSupervisorByKey($key)
            ?? throw $this->createNotFoundException(sprintf('Unknown supervisor server "%s".', $key));
    }

    /**
     * Resolves the supervisor while enforcing the hidden_processes deny-list.
     * Hidden processes 404 here, before any RPC call — they are not just
     * hidden in the UI, they cannot be targeted at all.
     */
    private function getSupervisorForVisibleProcess(string $key, string $group, string $name): Supervisor
    {
        $supervisor = $this->getSupervisor($key);

        if (!$this->supervisorManager->isProcessVisible($key, $group, $name)) {
            throw $this->createNotFoundException(sprintf('Process "%s:%s" not found.', $group, $name));
        }

        return $supervisor;
    }

    private function getProcessIdentification(string $group, string $name): string
    {
        return sprintf('%s:%s', $group, $name);
    }

    private function flashResult(bool $success, string $translationPrefix): void
    {
        $this->addFlash(
            $success ? 'success' : 'error',
            $this->translator->trans($translationPrefix . ($success ? '.success' : '.error'), [], 'SupervisorBundle'),
        );
    }
}
