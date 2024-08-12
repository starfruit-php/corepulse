<?php

namespace CorepulseBundle\Controller\Api;

use CorepulseBundle\Controller\Api\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Pimcore\Model\DataObject;
use Symfony\Component\Routing\Annotation\Route;
use Pimcore\Model\DataObject\Customer;
use Knp\Component\Pager\PaginatorInterface;
use CorepulseBundle\Model\TimeLine;
use CorepulseBundle\Services\TimeLineService;

class TimeLineController extends BaseController
{
    /**
     * @Route("/timeline/listing", name="timeline_listing", options={"expose"=true}))
     */
    public function listing(Request $request, PaginatorInterface $paginator)
    {
        $data = [];
        $response = [];
        try {

            $order_by = $request->get('order_by');
            $order = $request->get('order');

            $messageError = $this->validator->validate([
                'orderId' => 'required',
                'page' => $this->request->get('page') ? 'numeric|positive' : '',
                'limit' => $this->request->get('limit') ? 'numeric|positive' : '',
                'order_by' => $order_by ? 'choice:title' : '',
                'order' => $order ? 'choice:desc,asc' : ''
            ], $request);

            if ($messageError) return $this->sendError($messageError);

            if (empty($order_by)) $order_by = 'createAt';
            if (empty($order)) $order = 'desc';
            $listing = new TimeLine\Listing;

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
                    'idOrder' => $item->getIdOrder(),
                    'title' => $item->getTitle(),
                    'description' => $item->getDescription(),
                    'updateAt' => $item->getUpdateAt(),
                    'createAt' => $item->getCreateAt(),
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
     * @Route("/timeline/create", name="timeline_create", options={"expose"=true}))
     */
    public function create(Request $request, PaginatorInterface $paginator)
    {
        $options = [
            'idOrder' => 'required',
        ];

        $messageError = $this->validator->validate($options, $this->request);

        if ($messageError) return $this->sendError($messageError);

        $timeLine = TimeLineService::create([
            'title' => $request->get('title'),
            'idOrder' => $request->get('idOrder'),
            'description' => $request->get('description'),
        ]);
        if ($timeLine instanceof TimeLine) {
            return $this->sendResponse('timeLine.create.success');
        }

        return $this->sendError('timeLine.create.error');
    }

    /**
     * @Route("/timeline/update", name="timeline_update", options={"expose"=true}))
     */
    public function update(Request $request, PaginatorInterface $paginator)
    {
        $options = [
            'id' => 'required',
            'description' => 'required',
        ];

        $messageError = $this->validator->validate($options, $this->request);

        if ($messageError) return $this->sendError($messageError);

        $timeLine = TimeLine::getById($request->get('id'));

        $timeLine = TimeLineService::edit([
            'title' => $request->get('title'),
            'idOrder' => $request->get('idOrder'),
            'description' => $request->get('description'),
        ], $timeLine);

        if ($timeLine instanceof TimeLine) {
            return $this->sendResponse('timeLine.edit.success');
        }

        return $this->sendError('timeLine.edit.error');
    }

    /**
     * @Route("/timeline/delete", name="timeline_delete", options={"expose"=true}))
     */
    public function delete(Request $request, PaginatorInterface $paginator)
    {
        $options = [
            'id' => 'required'
        ];

        $messageError = $this->validator->validate($options, $this->request);

        if ($messageError) return $this->sendError($messageError);

        $timeLine = TimeLine::getById($request->get('id'));

        if ($timeLine instanceof TimeLine) {
            $timeLine = TimeLineService::delete($timeLine->getId());

            return $this->sendResponse('timeLine.delete.success');
        }

        return $this->sendError('timeLine.delete.error');
    }
}
