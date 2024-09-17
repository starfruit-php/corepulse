<?php

namespace CorepulseBundle\Controller\Api;

use CorepulseBundle\Controller\Api\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Pimcore\Model\DataObject;
use Symfony\Component\Routing\Annotation\Route;
use Pimcore\Model\DataObject\Customer;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @Route("/customer")
 */
class CustomerController extends BaseController
{
    /**
     * @Route("/listing", name="api_customer_listing")
     */
    public function listing()
    {
        try {
            $conditions = $this->getPaginationConditions($this->request, []);
            list($page, $limit, $condition) = $conditions;

            $messageError = $this->validator->validate($condition, $this->request);
            if($messageError) return $this->sendError($messageError);

            $filterRule = $this->request->get('filterRule');
            $filter = $this->request->get('filter');

            $conditionQuery = '';
            $conditionParams = [];

            if ($filterRule && $filter) {
                $arrQuery = $this->getQueryCondition($filterRule, $filter);

                if ($arrQuery['query']) {
                    $conditionQuery .= ' AND (' . $arrQuery['query'] . ')';
                    $conditionParams = array_merge($conditionParams, $arrQuery['params']);
                }
            }

            $orderKey = $this->request->get('order_by');
            $order = $this->request->get('order');
            if (empty($order_by)) $orderKey = 'key';
            if (empty($order)) $order = 'asc';

            if ($limit == -1) {
                $limit = 10000;
            }

            $listing = new Customer\Listing();
            $listing->setCondition($conditionQuery, $conditionParams);
            $listing->setOrderKey($orderKey);
            $listing->setOrder($order);
            $listing->setUnpublished(true);

            $pagination = $this->paginator($listing, $page, $limit);

            $data = [
                'paginationData' => $pagination->getPaginationData(),
            ];

            foreach($pagination as $item) {
                $data['data'][] =  [
                    'id' => $item->getId(),
                    'email' => $item->getEmail(),
                    'status' => $item->getPublished(),
                    'fullName' => $item->getFullName(),
                    'phone' => $item->getPhone(),
                ];
            }

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
}
