<?php

namespace CorepulseBundle\Controller\Api;

use CorepulseBundle\Services\DocumentServices;
use Pimcore\Translation\Translator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use DateTime;

/**
 * @Route("/document")
 */
class DocumentController extends BaseController
{
    /**
     * @Route("/listing", name="api_document_listing", methods={"GET"}, options={"expose"=true})
     *
     * {mô tả api}
     *
     * @param Cache $cache
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function listingAction(
        Request $request,
        PaginatorInterface $paginator
    ): JsonResponse {
        try {
            $this->setLocaleRequest();

            $orderByOptions = ['index'];
            $conditions = $this->getPaginationConditions($request, $orderByOptions);
            list($page, $limit, $condition) = $conditions;

            $condition = array_merge($condition, [
                'folderId' => '',
                'type' => '',
            ]);
            $messageError = $this->validator->validate($condition, $request);
            if ($messageError) return $this->sendError($messageError);

            $conditionQuery = 'id is not NULL';
            $conditionParams = [];

            $id = $request->get('folderId') ? $request->get('folderId') : 1;
            if ($id) {
                $conditionQuery .= ' AND parentId = :parentId';
                $conditionParams['parentId'] = $id;
            }

            $type = $request->get('type') ? $request->get('type') : '';
            if ($type) {
                $conditionQuery .= ' AND type = :type';
                $conditionParams['type'] = $type;
            }

            $list = new Document\Listing();
            $list->setOrderKey($request->get('order_by', 'index'));
            $list->setOrder($request->get('order', 'asc'));
            $list->setCondition($conditionQuery, $conditionParams);

            $paginationData = $this->helperPaginator($paginator, $list, $page, $limit);
            $data = array_merge(
                [
                    'data' => []
                ],
                $paginationData,
            );

            foreach ($list as $item) {
                $data['data'][] = self::listingResponse($item);
            }

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    // trả ra dữ liệu 
    public function listingResponse($item)
    {
        $publicURL = DocumentServices::getThumbnailPath($item);

        $draft = $this->checkLastest($item);
        if ($draft) {
            $status = 'Draft';
        } else {
            if ($item->getPublished()) {
                $status = 'Publish';
            } else {
                $status = 'Draft';
            }
        }

        $listChills = new \Pimcore\Model\Document\Listing();
        $listChills->setCondition("parentId = :parentId", ['parentId' => $item->getId()]);
        $chills = [];
        foreach ($listChills as $chill) {
            $chills[] = $chill;
        }

        $json[] = [
            'id' => $item->getId(),
            'name' => "<div class='tableCell--titleThumbnail d-flex align-center'><img class='me-2' src=' " .  $publicURL . "'><span>" . $item->getKey() . "</span></div>",
            'type' => '<div class="chip">' . $item->getType() . '</div>',
            'status' => $status,
            'createDate' => DocumentServices::getTimeAgo($item->getCreationDate()),
            'modificationDate' => DocumentServices::getTimeAgo($item->getModificationDate()),
            'parent' => $chills ? true : false,
            'noMultiEdit' => [
                'name' => $chills ? [] : ['name'],
            ],
            "noAction" =>  $chills ? [] : ['seeMore'],
        ];

        return $json;
    }
}
