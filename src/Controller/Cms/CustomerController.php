<?php

namespace CorepulseBundle\Controller\Cms;

use CorepulseBundle\Controller\Cms\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Pimcore\Model\DataObject;
use Symfony\Component\Routing\Annotation\Route;
use Pimcore\Model\DataObject\Customer;
use Knp\Component\Pager\PaginatorInterface;

class CustomerController extends BaseController
{
    /**
     * @Route("/customer/listing", name="customer_lists", options={"expose"=true}))
     */
    public function listing(Request $request, PaginatorInterface $paginator)
    {
        $viewData = [];

        return $this->renderWithInertia('Pages/Customer/Listing', [], $viewData);
    }
    /**
     * @Route("/customer/summary", name="customer_summary", options={"expose"=true}))
     */
    public function Summary(Request $request, PaginatorInterface $paginator)
    {
        $viewData = [];

        return $this->renderWithInertia('Pages/Customer/Summary', [], $viewData);
    }
}
