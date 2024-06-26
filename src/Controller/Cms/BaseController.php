<?php

declare(strict_types=1);

namespace CorepulseBundle\Controller\Cms;

use DateTime;
use Rompetomp\InertiaBundle\Service\InertiaInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use CorepulseBundle\Controller\Cms\Traits\BuildInertiaDefaultPropsTrait;
use CorepulseBundle\Model\User;
use ValidatorBundle\Validator\Validator;
use Pimcore\Templating\Renderer\EditableRenderer;
use Symfony\Component\Process\Process;

abstract class BaseController extends AbstractController
{
    use BuildInertiaDefaultPropsTrait;

    protected InertiaInterface $inertia;

    protected $validator;

    protected $request;

    protected $editableRender;

    public function __construct(RequestStack $requestStack, Validator $validator, EditableRenderer $editableRender)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->validator = $validator;
        $this->editableRender = $editableRender;
    }

    /**
     * @required
     */
    public function setInertia(InertiaInterface $inertia): void
    {
        $this->inertia = $inertia;
    }

    public function validate($param)
    {
        $messageError = $this->validator->validate($param, $this->request);
        return $messageError;
    }

    /**
     * @param array<string, mixed> $props
     * @param array<string, mixed> $viewData
     * @param array<string, mixed> $context
     */
    protected function renderWithInertia(
        string $component,
        array $props = [],
        array $viewData = [],
        array $context = []
    ): Response {
        /** @var ?User $currentUser */
        $currentUser = $this->getUser();
        // dd($this->request);
        $defaultProps = $this->buildDefaultProps($this->request, $currentUser);

        return $this->inertia->render($component, array_merge($defaultProps, $props), $viewData, $context);
    }

    protected function isGrantedBase(string $name)
    {
        if (!$this->isGranted(strtoupper($name))) {
            throw $this->createAccessDeniedException('Access denied.');
        }
    }

    protected function setRootView($template)
    {
        return $this->inertia->setRootView($template);
    }

    public function renderRenderlet($document, $type, $name, $option)
    {
        return $this->editableRender->render($document, $type, $name, $option, false);
    }

    public function getTimeAgo($timestamp)
    {
        // Create DateTime objects for the current time and the given timestamp
        $currentDateTime = new DateTime();
        $timestampDateTime = new DateTime("@$timestamp");

        // Calculate the difference between the current time and the given timestamp
        $interval = $currentDateTime->diff($timestampDateTime);

        // Format the result based on the difference
        if ($interval->y > 0) {
            return $interval->y . " year" . ($interval->y > 1 ? "s" : "") . " ago";
        } elseif ($interval->m > 0) {
            return $interval->m . " month" . ($interval->m > 1 ? "s" : "") . " ago";
        } elseif ($interval->d > 0) {
            return $interval->d . " day" . ($interval->d > 1 ? "s" : "") . " ago";
        } elseif ($interval->h > 0) {
            return $interval->h . " hour" . ($interval->h > 1 ? "s" : "") . " ago";
        } elseif ($interval->i > 0) {
            return $interval->i . " minute" . ($interval->i > 1 ? "s" : "") . " ago";
        } else {
            return "just now";
        }
    }

    public function checkLastest($object)
    {
        $lastest = $this->getLastest($object);

        if ($lastest) {
            return $object->getModificationDate() !== $lastest->getModificationDate();
        }
        return false;
    }

    public function getLastest($object)
    {
        $versions = $object->getVersions();

        if (empty($versions)) {
            return $object;
        }

        $previousVersion = $versions[count($versions) - 1];
        $previousObject = $previousVersion->getData();
        return $previousObject;
    }

    public function getPermission()
    {
        $user = $this->getUser();
        // Lấy quyền của role và quyền của user
        $rolePermission = ($user->getRole()) ? json_decode($user->getRole()->getPermission(), true) : [];
        $userPermission = $user->getPermission() ? json_decode($user->getPermission(), true) : [];

        $rolePermission = isset($rolePermission) && is_array($rolePermission) ? $rolePermission : [];
        $userPermission = isset($userPermission) && is_array($userPermission) ? $userPermission : [];

        // xử lý gộp quyền
        $mergedArray = array_merge($rolePermission, $userPermission);
        $uniqueRole = array_unique($mergedArray);
        $permission = array_values($uniqueRole);

        return $permission;
    }

    public function checkRole($permission)
    {
        if ($this->getUser()->getDefaultAdmin()) {
            return true;
        } else {
            $userPermission = $this->getPermission();

            if (in_array($permission, $userPermission)) return true;

            return false;
        }
    }

    public function getLanguage()
    {
        return \Pimcore\Tool::getDefaultLanguage();
    }

    public function runProcess($command)
    {
        try {
            $process = new Process(explode(' ', 'php ' . str_replace("\\", '/', PIMCORE_PROJECT_ROOT) . '/bin/console ' . $command), null, null, null, 900);

            $process->run();
        } catch (\Throwable $e) {

        }
    }
}
