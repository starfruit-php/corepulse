<?php

declare(strict_types=1);

namespace CorepulseBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use Pimcore\Model\User;
use Rompetomp\InertiaBundle\Service\InertiaInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use CorepulseBundle\Controller\Admin\Traits\BuildInertiaDefaultPropsTrait;

abstract class BaseController extends AbstractController
{
    use BuildInertiaDefaultPropsTrait;

    protected InertiaInterface $inertia;

    protected $validator;

    protected $request;

    protected $csrfProtection;

    public function __construct(RequestStack $requestStack, CsrfProtectionHandler $csrfProtection)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->csrfProtection = $csrfProtection;
    }

    /**
     * @required
     */
    public function setInertia(InertiaInterface $inertia): void
    {
        $this->inertia = $inertia;
    }

    public function setValidator($validator)
    {
        $this->validator = $validator;
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

        $defaultProps = $this->buildDefaultProps($this->request, $currentUser);

        $props['csrf_token'] = $this->csrfProtection->getCsrfToken($this->request->getSession());
        // dd($defaultProps, $props);
        return $this->inertia->render($component, array_merge($defaultProps, $props), $viewData, $context);
    }

    protected function isGrantedBase(string $name)
    {
        if (!$this->isGranted(strtoupper($name))) {
            throw $this->createAccessDeniedException('Access denied.');
        }
    }
}
