<?php

declare(strict_types=1);

namespace CorepulseBundle\Controller\Cms\Traits;

use Symfony\Component\HttpFoundation\Session\Session;

trait BuildInertiaDefaultPropsTrait
{
    /**
     * @return array<string, mixed>
     */
    protected function buildDefaultProps($request, $user): array
    {
        $flashSuccessMessage = null;
        $flashErrorMessage = null;
        $flashErrorsMessage = null;

        // @phpstan-ignore-next-line
        if ($request->hasSession()) {
            /** @var Session $session */
            $session = $request->getSession();

            if ($session->getFlashBag()->has('success')) {
                $flashSuccessMessages = $session->getFlashBag()->get('success');
                $flashSuccessMessage = reset($flashSuccessMessages);
            }

            if ($session->getFlashBag()->has('error')) {
                $flashErrorMessages = $session->getFlashBag()->get('error');
                $flashErrorMessage = reset($flashErrorMessages);
            }
            if ($session->getFlashBag()->has('errors')) {
                $flashErrorsMessages = $session->getFlashBag()->get('errors');
                $flashErrorsMessage = reset($flashErrorsMessages);
            }
        }
        
        $changeUser = true;

        // if ($user && $user->getDefaultAdmin()) {
        //     $changeUser = false;
        // }

        return [
            'error' => null,
            'flash' => [
                'success' => $flashSuccessMessage,
                'error' => $flashErrorMessage,
                'errors' => $flashErrorsMessage,
            ],
            'changeUser' => $changeUser,
        ];
    }
}
