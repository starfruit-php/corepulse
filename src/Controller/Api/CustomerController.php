<?php

namespace CorepulseBundle\Controller\Api;

use CorepulseBundle\Controller\Api\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Pimcore\Model\DataObject;
use Symfony\Component\Routing\Annotation\Route;
use Pimcore\Model\DataObject\Customer;
use Knp\Component\Pager\PaginatorInterface;

class CustomerController extends BaseController
{
    /**
     * @Route("/customer/listing", name="customer_listing", options={"expose"=true}))
     */
    public function listing(Request $request, PaginatorInterface $paginator)
    {
        $data = [];
        $response = [];
        try {

            $order_by = $request->get('order_by');
            $order = $request->get('order');

            $messageError = $this->validator->validate([
                'page' => $this->request->get('page') ? 'numeric|positive' : '',
                'limit' => $this->request->get('limit') ? 'numeric|positive' : '',
                'order_by' => $order_by ? 'choice:title' : '',
                'order' => $order ? 'choice:desc,asc' : ''
            ], $request);

            if ($messageError) return $this->sendError($messageError);

            if (empty($order_by)) $order_by = 'username';
            if (empty($order)) $order = 'desc';
            $listing = new Customer\Listing;

            if (!empty($request->get('search'))) $listing->addConditionParam("username LIKE '%" . $request->get('search') . "%'");
            $listing->setOrderKey($order_by);
            $listing->setOrder($order);


            $pagination = $paginator->paginate(
                $listing,
                $request->get('page', 1),
                $request->get('limit', 10),
            );

            foreach ($listing as $item) {
                $dataJson = [
                    'id' => $item->getId(),
                    'email' => $item->getEmail(),
                    'status' => $item->getPublished(),
                    'fullName' => $item->getFullName(),
                    'phone' => $item->getPhone(),
                ];

                array_push($data, $dataJson);
            }

            $response['data'] = $data;
            $response['paginator'] = $pagination->getPaginationData();
        } catch (\Throwable $e) {

            return $this->sendError($e->getMessage(), 500);
        }

        return $this->sendResponse($response);
    }
}
