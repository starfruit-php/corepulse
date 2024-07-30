<?php

namespace CorepulseBundle\Controller\Api;

use Symfony\Component\Routing\Annotation\Route;
use CorepulseBundle\Model\Indexing;
use CorepulseBundle\Services\SeoServices;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/indexing")
 */
class IndexingController extends BaseController
{
    /**
     * @Route("/listing", name="api_indexing_listing", methods={"GET"})
     *
     * {mô tả api}
     */
    public function listingAction()
    {
        try {
            $orderByOptions = ['id', 'time', 'url', 'type', 'response'];
            $conditions = $this->getPaginationConditions($this->request, $orderByOptions);
            list($page, $limit, $condition) = $conditions;

            $messageError = $this->validator->validate($condition, $this->request);
            if($messageError) return $this->sendError($messageError);

            $conditionQuery = 'id is not NULL';
            $conditionParams = [];

            $listing = new Indexing\Listing();
            $listing->setOrderKey($this->request->get('order_by', 'updateAt'));
            $listing->setOrder($this->request->get('order', 'desc'));
            $listing->setCondition($conditionQuery, $conditionParams);
            $listing->load();

            $pagination = $this->paginator($listing, $page, $limit);

            $data = [
                'data' => [],
                'paginationData' =>$pagination->getPaginationData(),
            ];

            foreach($listing as $item) {
                $data['data'][] =  [
                    'id' => $item->getId(),
                    'time' => $item->getTime() ? $item->getTime() : $item->getUpdateAt(),
                    'url' => $item->getUrl(),
                    'type' => $item->getType(),
                    'response' => $item->getResponse(),
                ];
            }

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * @Route("/setting", name="api_indexing_setting", methods={"GET", "POST"})
     *
     * {mô tả api}
     */
    public function settingAction()
    {
        try {
            if ($this->request->getMethod() == Request::METHOD_POST) {
                $condition = [
                    'json' => 'json',
                    'classes' => 'json',
                    'documents' => 'json',
                    'file' => 'file:mimeTypes,application/json'
                ];

                $messageError = $this->validator->validate($condition, $this->request);
                if($messageError) return $this->sendError($messageError);

                $params = [];

                $arrayKey = ['json', 'classes', 'documents'];
                foreach ($arrayKey as $key) {
                    if ($value = $this->request->get($key)) {
                        $params[$key] = $value;
                    }
                }

                if ($file = $this->request->files->get('file')) {
                    $params['file'] = $file;
                }

                $data = SeoServices::setIndexSetting($params);

                return $this->sendResponse($data);
            }

            $data = SeoServices::getIndexContent();

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
}
