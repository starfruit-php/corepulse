<?php

namespace CorepulseBundle\Controller\Cms;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Pimcore\Model\DataObject\Service as DataObjectService;
use Pimcore\Model\DataObject;
use Knp\Component\Pager\PaginatorInterface;
use phpDocumentor\Reflection\Types\Parent_;
use ValidatorBundle\Validator\Validator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Pimcore\Model\DataObject\Role;
use Pimcore\Db;
use CorepulseBundle\Services\TranslationsServices;
use Symfony\Component\HttpFoundation\JsonResponse;

class TestController extends BaseController
{
    /**
     *
     * @Route("/test", name="test", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function listing(TranslatorInterface $translator, Request $request)
    {
        dd(123);
        return $this->renderWithInertia('Pages/Test', []);
    }
}
