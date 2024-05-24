<?php

namespace CorepulseBundle\Controller\Cms;

use Symfony\Component\Routing\Annotation\Route;
use CorepulseBundle\Model\Role;
use CorepulseBundle\Model\User;
use Symfony\Component\HttpFoundation\Request;
use Pimcore\Model\DataObject\Service as DataObjectService;
use Pimcore\Model\DataObject;
use Knp\Component\Pager\PaginatorInterface;
use phpDocumentor\Reflection\Types\Parent_;
use ValidatorBundle\Validator\Validator;
use Symfony\Component\HttpFoundation\RequestStack;
use CorepulseBundle\Services\RoleServices;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductController extends BaseController
{
    /**
     *
     * @Route("/product/listing", name="product_listing", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function listing(Request $request)
    {
        return $this->renderWithInertia('Pages/Product/Listing', []);
    }
}
