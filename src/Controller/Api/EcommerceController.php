<?php

namespace CorepulseBundle\Controller\Api;

use Pimcore\Translation\Translator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use CorepulseBundle\Services\AssetServices;
use Pimcore\Model\Asset;
use DateTime;
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
     * @Route("/summary", name="summary", methods={"GET"})
     */
    public function summary(Request $request, PaginatorInterface $paginator)
    {
        // $user = $request->get('id');
        $user = 118;
        $totalPrice = 0;

        $order = new OnlineShopOrder\Listing();
        $order->addConditionParam('customer__id', $user);

        $totalOrder = $order->count();

        foreach ($order as $items) {
            $totalPrice += $items->getTotalPrice();
        }

        $user = DataObject::getById($user);

        // Bổ sung dữ liệu ở đây
        $data = [];
        $data['totalPrice'] = number_format($totalPrice, 0, ".", ".");
        $data['totalOrder'] = $totalOrder;
        $data['email'] = $user->getEmail();
        $data['phone'] = $user->getPhone();
        $data['fullName'] = $user->getFullName();
        $data['company'] = property_exists($user, 'company') ? $user->getCompany() : '';
        $data['address'] = property_exists($user, 'address') ? $user->getAddress() : '';

        return $this->sendResponse($data);
    }

    /**
     * @Route("/order/listing", name="order_listing", methods={"GET"})
     */
    public function order(Request $request, PaginatorInterface $paginator)
    {
        // $user = $request->get('user');
        $user = 118;
        $totalPrice = 0;

        $data = [];
        $response = [];
        try {

            $order_by = $request->get('order_by');
            $order = $request->get('order');

            $messageError = $this->validator->validate([
                'customer' => 'required',
                'page' => $this->request->get('page') ? 'numeric|positive' : '',
                'limit' => $this->request->get('limit') ? 'numeric|positive' : '',
                'order_by' => $order_by ? 'choice:title' : '',
                'order' => $order ? 'choice:desc,asc' : ''
            ], $request);

            if ($messageError) return $this->sendError($messageError);



            if (empty($order_by)) $order_by = 'ordernumber';
            if (empty($order)) $order = 'desc';
            $listing = new OnlineShopOrder\Listing;
            $listing->addConditionParam('customer__id', $user);
            if (!empty($request->get('search'))) $listing->addConditionParam("ordernumber LIKE '%" . $request->get('search') . "%'");
            $listing->setOrderKey($order_by);
            $listing->setOrder($order);


            $pagination = $paginator->paginate(
                $listing,
                $request->get('page', 1),
                $request->get('limit', 10),
            );

            foreach ($listing as $item) {
                $child = [];

                foreach ($item->getItems() as $value) {
                    $child[] = [
                        'product' => $value->getProduct() ? $value->getProduct()->getProductName() : '',
                        'amount' => $value->getAmount(),
                        'totalPrice' => $value->getTotalPrice(),

                    ];
                }

                $dataJson = [
                    'id' => $item->getId(),
                    'ordernumber' => $item->getOrdernumber(),
                    'orderdate' => $item->getOrderdate(),
                    'totalPrice' => $item->getTotalPrice(),
                    'totalNetPrice' => $item->getTotalNetPrice(),
                    'customer' => $item->getCustomer()->getId(),
                    'subTotalNetPrice' => $item->getSubTotalNetPrice(),
                    'item' => $child
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


    /**
     * @Route("/order/update", name="order_update", methods={"POST"})
     */
    public function update(Request $request, PaginatorInterface $paginator)
    {

        $messageError = $this->validator->validate([
            'id' => 'required',
        ], $request);
        if ($messageError) return $this->sendError($messageError);
        dd(2);
        $params = [
            'username' => $request->get('username'),
            'phone' => $request->get('phone'),
            'city' => $request->get('city'),
            'district' => $request->get('district'),
            'precinct' => $request->get('precinct'),
            'address' => $request->get('address'),
            'referralCode' => $request->get('referralCode'),
            'typeCooperation' => $request->get('typeCooperation'),
            'identification' => $request->get('identification'),
            'cityView' => trim($request->get('cityView')),
            'districtView' => trim($request->get('districtView')),
            'precinctView' => trim($request->get('precinctView')),
        ];

        // $edit = AffiliateService::edit($this->getUser(), $params);

        $data = [
            'success' => true,
            'message' => $this->translator->trans('Đã cập nhật thành công chờ duyệt!'),
        ];

        return $this->sendResponse($data);
    }
}
