<?php

namespace CorepulseBundle\Controller\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Pimcore\Model\DataObject\OnlineShopOrder;
use Pimcore\Model\DataObject;

/**
 * @Route("/ecommerce")
 */
class EcommerceController extends BaseController
{
    const PAGE_DEFAULT = 1;
    const PERPAGE_DEFAULT = 10;

    /**
     * @Route("/summary", name="api_ecommerce_summary", methods={"GET"})
     */
    public function summary()
    {
        try {
            $conditions = $this->getPaginationConditions($this->request, []);
            list($page, $limit, $condition) = $conditions;

            $condition = array_merge($condition, [
                'customer' => 'required',
            ]);

            $messageError = $this->validator->validate($condition, $this->request);
            if($messageError) return $this->sendError($messageError);

            $customerId = $this->request->get('customer');
            $customer = DataObject\Customer::getById($customerId);

            if (!$customer) return $this->sendError([ 'success' => false, 'message' => 'Customer not found.' ]);
            $filterRule = $this->request->get('filterRule');
            $filter = $this->request->get('filter');

            $conditionQuery = 'customer__id = ?';
            $conditionParams = [$customer->getId()];

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

            $listing = new OnlineShopOrder\Listing();
            $listing->setCondition($conditionQuery, $conditionParams);
            $listing->setOrderKey($orderKey);
            $listing->setOrder($order);
            $listing->setUnpublished(true);

            $totalPrice = 0;
            foreach ($listing as $item) {
                $totalPrice += $item->getTotalPrice();
            }

            // Bổ sung dữ liệu ở đây
            $data = [];
            $data['totalPrice'] = number_format($totalPrice, 0, ".", ".");
            $data['totalOrder'] = $listing->count();
            $data['email'] = $customer->getEmail();
            $data['phone'] = $customer->getPhone();
            $data['fullName'] = $customer->getFullName();
            $data['company'] = property_exists($customer, 'company') ? $customer->getCompany() : '';
            $data['address'] = property_exists($customer, 'address') ? $customer->getAddress() : '';

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * @Route("/order/listing", name="api_ecommerce_order_listing", methods={"GET"})
     */
    public function order(Request $request, PaginatorInterface $paginator)
    {
        try {
            $conditions = $this->getPaginationConditions($this->request, []);
            list($page, $limit, $condition) = $conditions;

            $condition = array_merge($condition, [
                'customer' => 'numeric',
            ]);

            $messageError = $this->validator->validate($condition, $this->request);
            if($messageError) return $this->sendError($messageError);

            $conditionQuery = '';
            $conditionParams = [];

            $customerId = $this->request->get('customer');
            if ($customerId) {
                $customer = DataObject\Customer::getById($customerId);
                if (!$customer) return $this->sendError([ 'success' => false, 'message' => 'Customer not found.' ]);
                $conditionQuery = 'customer__id = ?';
                $conditionParams = [$customer->getId()];
            }

            $filterRule = $this->request->get('filterRule');
            $filter = $this->request->get('filter');

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

            $listing = new OnlineShopOrder\Listing();
            $listing->setCondition($conditionQuery, $conditionParams);
            $listing->setOrderKey($orderKey);
            $listing->setOrder($order);
            $listing->setUnpublished(true);

            $pagination = $this->paginator($listing, $page, $limit);

            $data = [
                'paginationData' => $pagination->getPaginationData(),
            ];

            foreach ($listing as $item) {
                $child = [];

                foreach ($item->getItems() as $value) {
                    $child[] = [
                        'product' => $value->getProduct() ? $value->getProduct()->getProductName() : '',
                        'amount' => $value->getAmount(),
                        'totalPrice' => $value->getTotalPrice(),
                    ];
                }

                $data['data'][] = [
                    'id' => $item->getId(),
                    'ordernumber' => $item->getOrdernumber(),
                    'orderdate' => $item->getOrderdate(),
                    'totalPrice' => $item->getTotalPrice(),
                    'totalNetPrice' => $item->getTotalNetPrice(),
                    'customer' => $item->getCustomer()?->getId(),
                    'subTotalNetPrice' => $item->getSubTotalNetPrice(),
                    'items' => $child
                ];
            }

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * @Route("/order/detail", name="api_ecommerce_order_detail", methods={"GET", "POST"})
     */
    public function detail()
    {
        try {
            
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
}
