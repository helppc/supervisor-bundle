<?php declare(strict_types=1);

namespace HelpPC\Bundle\SupervisorBundle\Controller;

use HelpPC\Bundle\SupervisorBundle\Manager\SupervisorManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * SupervisorController
 */
class SupervisorController extends AbstractController
{
    /** @var string[] */
    private static array $publicInformatics = ['description', 'group', 'name', 'state', 'statename'];

    private SupervisorManager $supervisorManager;
    private TranslatorInterface $translator;

    public function __construct(SupervisorManager $supervisorManager, TranslatorInterface $translator)
    {
        $this->supervisorManager = $supervisorManager;
        $this->translator = $translator;
    }

    public function indexAction(): Response
    {
        return $this->render('@Supervisor/Supervisor/list.html.twig', [
            'supervisors' => $this->supervisorManager->getSupervisors(),
        ]);
    }

    public function startStopProcessAction(bool $start, string $key, string $name, string $group, Request $request): Response
    {
        $success = false;
        $supervisor = $this->supervisorManager
            ->getSupervisorByKey($key);

        $flashes = [];
        try {
            if ($start === true) {
                $success = $supervisor->startProcess($this->getProcessIdentification($group, $name));
            } elseif ($start === false) {
                $success = $supervisor->stopProcess($this->getProcessIdentification($group, $name));
            }

        } catch (\Exception $e) {
            $success = false;
            $flashes[] = $this->translator->trans('process.stop.error', [], 'SupervisorBundle');
        }

        if (!$success) {
            $flashes[] = $this->translator->trans(
                ($start === true ? 'process.start.error' : 'process.stop.error'),
                [],
                'SupervisorBundle'
            );
        }

        if ($request->isXmlHttpRequest()) {
            $processInfo = $supervisor->getProcessInfo($this->getProcessIdentification($group, $name));

            return new JsonResponse([
                'success' => $success,
                'message' => implode(', ', $flashes ?? []),
                'processInfo' => $processInfo,
            ],
                JsonResponse::HTTP_OK,
                ['Cache-Control' => 'no-store']
            );
        }
        foreach ($flashes as $messages) {
            $this->addFlash('error', $messages);
        }

        return $this->redirect($this->generateUrl('supervisor'));
    }

    public function startStopAllProcessesAction(Request $request, bool $start, string $key): Response
    {
        $processesInfo = true;
        if ($start === true) {
            $processesInfo = $this->supervisorManager
                ->getSupervisorByKey($key)
                ->startAllProcesses(false);
        } elseif ($start === false) {
            $processesInfo = $this->supervisorManager
                ->getSupervisorByKey($key)
                ->stopAllProcesses(false);
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'processesInfo' => $processesInfo,
            ],
                JsonResponse::HTTP_OK,
                ['Cache-Control' => 'no-store']
            );
        }

        return $this->redirect($this->generateUrl('supervisor'));
    }

    public function showSupervisorLogAction(string $key): Response
    {
        $supervisor = $this->supervisorManager
            ->getSupervisorByKey($key);

        if (!$supervisor) {
            throw new \Exception('Supervisor not found');
        }

        $logs = $supervisor->readLog(0, 0);

        return $this->render('@Supervisor/Supervisor/showLog.html.twig', [
            'log' => $logs,
        ]);
    }

    public function clearSupervisorLogAction(string $key): Response
    {
        $supervisor = $this->supervisorManager
            ->getSupervisorByKey($key);

        if (!$supervisor) {
            throw new \Exception('Supervisor not found');
        }

        if ($supervisor->clearLog() !== true) {
            $this->addFlash(
                'error',
                $this->translator->trans('logs.delete.error', [], 'SupervisorBundle')
            );
        }

        return $this->redirect($this->generateUrl('supervisor'));
    }

    public function showProcessLogAction(string $key, string $name, string $group): Response
    {
        $supervisor = $this->supervisorManager
            ->getSupervisorByKey($key);

        if (!$supervisor) {
            throw new \Exception('Supervisor not found');
        }

        $result = $supervisor->tailProcessStdoutLog($this->getProcessIdentification($group, $name), 0, 1);
        $stdout = $supervisor->tailProcessStdoutLog($this->getProcessIdentification($group, $name), 0, (int) $result[1]);

        return $this->render('@Supervisor/Supervisor/showLog.html.twig', [
            'log' => $stdout[0],
        ]);
    }

    public function showProcessLogErrAction(string $key, string $name, string $group): Response
    {
        $supervisor = $this->supervisorManager->getSupervisorByKey($key);

        if (!$supervisor) {
            throw new \Exception('Supervisor not found');
        }

        $result = $supervisor->tailProcessStderrLog($this->getProcessIdentification($group, $name), 0, 1);
        $stderr = $supervisor->tailProcessStderrLog($this->getProcessIdentification($group, $name), 0, (int) $result[1]);

        return $this->render('@Supervisor/Supervisor/showLog.html.twig', [
            'log' => $stderr[0],
        ]);
    }


    public function clearProcessLogAction(string $key, string $name, string $group): Response
    {
        $supervisor = $this->supervisorManager->getSupervisorByKey($key);

        if (!$supervisor) {
            throw new \Exception('Supervisor not found');
        }

        if ($supervisor->clearProcessLogs($this->getProcessIdentification($group, $name)) !== true) {
            $this->addFlash(
                'error',
                $this->translator->trans('logs.delete.error', [], 'SupervisorBundle')
            );
        }

        return $this->redirect($this->generateUrl('supervisor'));
    }

    public function showProcessInfoAction(string $key, string $name, string $group, Request $request): Response
    {
        $supervisor = $this->supervisorManager->getSupervisorByKey($key);

        if (!$supervisor) {
            throw new \Exception('Supervisor not found');
        }

        $infos = $supervisor->getProcessInfo($this->getProcessIdentification($group, $name));

        return $this->render('@Supervisor/Supervisor/showInformations.html.twig', [
            'informations' => $infos,
        ]);
    }

    public function showProcessInfoAllAction(string $key, Request $request): Response
    {
        if (!$request->isXmlHttpRequest()) {
            throw new \Exception('Ajax request expected here');
        }

        $supervisor = $this->supervisorManager->getSupervisorByKey($key);

        if (!$supervisor) {
            throw new \Exception('Supervisor not found');
        }

        $processes = $supervisor->getAllProcesses();

        $processesInfo = [];
        foreach ($processes as $process) {
            $infos = $supervisor->getProcessInfo($this->getProcessIdentification($process->getPayload()['group'], $process->getName()));
            $processInfo = [];
            foreach (self::$publicInformatics as $public) {
                $processInfo[$public] = $infos[$public];
            }

            $processesInfo[$infos['name']] = [
                'supervisor' => $key,
                'processInfo' => $processInfo,
                'controlLink' => $this->generateUrl('supervisor.process.startStop', [
                    'key' => $key,
                    'name' => $infos['name'],
                    'group' => $infos['group'],
                    'start' => ($infos['state'] == 10 || $infos['state'] == 20 ? '0' : '1'),
                ]),
            ];
        }

        return new JsonResponse($processesInfo,
            JsonResponse::HTTP_OK,
            ['Cache-Control' => 'no-store']
        );
    }

    private function getProcessIdentification(string $group, string $name): string
    {
        return sprintf('%s:%s', $group, $name);
    }
}
