<?php

namespace CorepulseBundle\Controller\Cms;

use CorepulseBundle\Controller\Cms\BaseController;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Routing\Annotation\Route;


class CustomerController extends BaseController
{
   /**
     * @Route("/customer/listing", name="customer_listing", options={"expose"=true}))
     */
    public function listing(Request $request)
    {
        $viewData = [];

        return $this->renderWithInertia('Pages/Customer/Listing', [], $viewData);
    }
}
