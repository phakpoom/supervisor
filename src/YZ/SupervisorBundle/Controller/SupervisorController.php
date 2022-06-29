<?php

namespace YZ\SupervisorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use YZ\SupervisorBundle\Manager\SupervisorManager;

class SupervisorController extends AbstractController
{
    /** @var SupervisorManager */
    private $manager;

    /** @var SessionInterface */
    private $session;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(SupervisorManager $manager, SessionInterface $session, ?TranslatorInterface $translator)
    {
        $this->manager = $manager;
        $this->session = $session;
        $this->translator = $translator;
    }

    private static $publicInformations = ['description', 'group', 'name', 'state', 'statename'];

    public function indexAction(): Response
    {
        return $this->render('@YZSupervisor/Supervisor/list.html.twig', [
            'supervisors' => $this->manager->getSupervisors()
        ]);
    }

    public function clearProcessLogAllAction(string $key):  Response
    {
        $supervisorManager = $this->get('supervisor.manager');
        $supervisor = $supervisorManager->getSupervisorByKey($key);

        if (!$supervisor) {
            throw new \Exception('Supervisor not found');
        }

        foreach ($supervisor->getProcesses() as $process) {
            if ($process->clearProcessLogs() !== true) {
                $this->session->getFlashBag()->add(
                    'error',
                    $this->translator->trans('logs.delete.error', [], 'YZSupervisorBundle')
                );
            }
        }

        return $this->redirect($this->generateUrl('supervisor'));
    }

    public function startStopProcessAction(string $start, string $key, string $name, string $group, Request $request): Response
    {
        $supervisor = $this->manager->getSupervisorByKey($key);

        if (!$supervisor) {
            throw new \Exception('Supervisor not found');
        }

        $process = $supervisor->getProcessByNameAndGroup($name, $group);
        try {
            if ($start == "1") {
                $success = $process->startProcess();
            } elseif ($start == "0") {
                $success = $process->stopProcess();
            } else {
                $success = false;
            }
        } catch (\Exception $e) {
            $success = false;
            $this->session->getFlashBag()->add(
                'error',
                $this->translator->trans('process.stop.error', [], 'YZSupervisorBundle')
            );
        }

        if (!$success) {
            $this->session->getFlashBag()->add(
                'error',
                $this->translator->trans(
                    ($start == "1" ? 'process.start.error' : 'process.stop.error'),
                    [],
                    'YZSupervisorBundle'
                )
            );
        }

        if ($request->isXmlHttpRequest()) {
            $processInfo = $process->getProcessInfo();
            $res = json_encode([
                'success' => $success,
                'message' => implode(', ', $this->session->getFlashBag()->get('error', [])),
                'processInfo' => $processInfo
            ]);

            return new Response($res, 200, [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-store',
            ]);
        }

        return $this->redirect($this->generateUrl('supervisor'));
    }

    public function startStopAllProcessesAction(Request $request, string $start, string $key): Response
    {
        $supervisor = $this->manager->getSupervisorByKey($key);

        if (!$supervisor) {
            throw new \Exception('Supervisor not found');
        }

        $processesInfo = true;
        if ($start == "1") {
            $processesInfo = $supervisor->startAllProcesses(false);
        } elseif ($start == "0") {
            $processesInfo = $supervisor->stopAllProcesses(false);
        }

        if ($request->isXmlHttpRequest()) {
            $res = json_encode([
                'processesInfo' => $processesInfo
            ]);

            return new Response($res, 200, [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-store',
            ]);
        }

        return $this->redirect($this->generateUrl('supervisor'));
    }

    public function showSupervisorLogAction(string $key): Response
    {
        $supervisorManager = $this->manager;
        $supervisor = $supervisorManager->getSupervisorByKey($key);

        if (!$supervisor) {
            throw new \Exception('Supervisor not found');
        }

        $logs = $supervisor->readLog(0, 0);

        return $this->render('@YZSupervisor/Supervisor/showLog.html.twig', [
            'log' => $logs
        ]);
    }

    public function clearSupervisorLogAction(string $key): Response
    {
        $supervisorManager = $this->manager;
        $supervisor = $supervisorManager->getSupervisorByKey($key);

        if (!$supervisor) {
            throw new \Exception('Supervisor not found');
        }

        if ($supervisor->clearLog() !== true) {
            $this->session->getFlashBag()->add(
                'error',
                $this->translator->trans('logs.delete.error', [], 'YZSupervisorBundle')
            );
        }

        return $this->redirect($this->generateUrl('supervisor'));
    }

    public function showProcessLogAction(string $key, string $name, string $group): Response
    {
        $supervisorManager = $this->manager;
        $supervisor = $supervisorManager->getSupervisorByKey($key);
        $process = $supervisor->getProcessByNameAndGroup($name, $group);

        if (!$supervisor) {
            throw new \Exception('Supervisor not found');
        }

        $result = $process->tailProcessStdoutLog(0, 1);
        $stdout = $process->tailProcessStdoutLog(0, $result[1]);

        return $this->render('@YZSupervisor/Supervisor/showLog.html.twig', [
            'log' => $stdout[0]
        ]);
    }

    public function showProcessLogErrAction(string $key, string $name, string $group): Response
    {
        $supervisorManager = $this->manager;
        $supervisor = $supervisorManager->getSupervisorByKey($key);
        $process = $supervisor->getProcessByNameAndGroup($name, $group);

        if (!$supervisor) {
            throw new \Exception('Supervisor not found');
        }

        $result = $process->tailProcessStderrLog(0, 1);
        $stderr = $process->tailProcessStderrLog(0, $result[1]);

        return $this->render('@YZSupervisor/Supervisor/showLog.html.twig', [
            'log' => $stderr[0]
        ]);
    }

    public function clearProcessLogAction(string $key, string $name, string $group): Response
    {
        $supervisorManager = $this->manager;
        $supervisor = $supervisorManager->getSupervisorByKey($key);
        $process = $supervisor->getProcessByNameAndGroup($name, $group);

        if (!$supervisor) {
            throw new \Exception('Supervisor not found');
        }

        if ($process->clearProcessLogs() !== true) {
            $this->session->getFlashBag()->add(
                'error',
                $this->translator->trans('logs.delete.error', [], 'YZSupervisorBundle')
            );
        }

        return $this->redirect($this->generateUrl('supervisor'));
    }

    public function showProcessInfoAction(string $key, string $name, string $group, Request $request): Response
    {
        $supervisorManager = $this->manager;
        $supervisor = $supervisorManager->getSupervisorByKey($key);
        $process = $supervisor->getProcessByNameAndGroup($name, $group);

        if (!$supervisor) {
            throw new \Exception('Supervisor not found');
        }

        $infos = $process->getProcessInfo();

        if ($request->isXmlHttpRequest()) {
            $processInfo = [];
            foreach (self::$publicInformations as $public) {
                $processInfo[$public] = $infos[$public];
            }

            $res = json_encode([
                'supervisor' => $key,
                'processInfo' => $processInfo,
                'controlLink' => $this->generateUrl('supervisor.process.startStop', [
                    'key' => $key,
                    'name' => $name,
                    'group' => $group,
                    'start' => ($infos['state'] == 10 || $infos['state'] == 20 ? '0' : '1')
                ])
            ]);

            return new Response($res, 200, [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-store',
            ]);
        }

        return $this->render('@YZSupervisor/Supervisor/showInformations.html.twig', [
            'informations' => $infos,
        ]);
    }

    public function showProcessInfoAllAction(string $key, Request $request): Response
    {
        if (!$request->isXmlHttpRequest()) {
            throw new \Exception('Ajax request expected here');
        }

        $supervisorManager = $this->manager;
        $supervisor = $supervisorManager->getSupervisorByKey($key);

        if (!$supervisor) {
            throw new \Exception('Supervisor not found');
        }

        $processes = $supervisor->getProcesses();
        $processesInfo = [];
        foreach ($processes as $process) {
            $infos = $process->getProcessInfo();
            $processInfo = [];
            foreach (self::$publicInformations as $public) {
                $processInfo[$public] = $infos[$public];
            }

            $processesInfo[$infos['name']] = [
                'supervisor' => $key,
                'processInfo' => $processInfo,
                'controlLink' => $this->generateUrl('supervisor.process.startStop', [
                    'key' => $key,
                    'name' => $infos['name'],
                    'group' => $infos['group'],
                    'start' => ($infos['state'] == 10 || $infos['state'] == 20 ? '0' : '1')
                ])
            ];
        }

        $res = json_encode($processesInfo);

        return new Response($res, 200, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store',
        ]);
    }
}
