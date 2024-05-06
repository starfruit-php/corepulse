<?php

namespace CorepulseBundle\Controller;

use Rompetomp\InertiaBundle\Service\InertiaInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use CorepulseBundle\Controller\Cms\BaseController;

class DefaultController extends BaseController
{
    /**
     * @Route("/cms")
     */
    public function indexAction(Request $request, InertiaInterface $inertia): Response
    {

        return $this->redirectToRoute('vuetify_dashboard');
    }
}
