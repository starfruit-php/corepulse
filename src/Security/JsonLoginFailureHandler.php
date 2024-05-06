<?php

declare(strict_types=1);

namespace CorepulseBundle\Security;

use Rompetomp\InertiaBundle\Service\InertiaInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use CorepulseBundle\Controller\Cms\Traits\BuildInertiaDefaultPropsTrait;
use Pimcore\Db;

class JsonLoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    use BuildInertiaDefaultPropsTrait;

    protected InertiaInterface $inertia;

    protected RouterInterface $router;

    public function __construct(InertiaInterface $inertia, RouterInterface $router)
    {
        $this->inertia = $inertia;
        $this->router = $router;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $props = $this->buildDefaultProps($request, null);

        $props['error'] = $exception->getMessage();

        $loginSetting = Db::get()->fetchAssociative('SELECT * FROM `vuetify_settings` WHERE `type` = "login"', []);
        if ($loginSetting && $loginSetting['config']) {
            $config = json_decode($loginSetting['config'], true);
        } else {
            $config = [];
        }

        $props['data'] = $config;

        return $this->inertia->render('Auth/Login', $props);
    }
}
