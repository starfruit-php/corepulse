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

/**
 * @Route("/asset")
 */
class AssetController extends BaseController
{
    /**
     * @Route("/listing", name="api_asset_listing", methods={"GET"}, options={"expose"=true})
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
        PaginatorInterface $paginator): JsonResponse
    {
        try {
            $this->setLocaleRequest();

            $orderByOptions = ['mimetype'];
            $conditions = $this->getPaginationConditions($request, $orderByOptions);
            list($page, $limit, $condition) = $conditions;

            $condition = array_merge($condition, [
                'folderId' => '',
                'type' => '',
            ]);
            $messageError = $this->validator->validate($condition, $request);
            if($messageError) return $this->sendError($messageError);

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

            $list = new Asset\Listing();
            $list->setOrderKey($request->get('order_by', 'mimetype'));
            $list->setOrder($request->get('order', 'asc'));
            $list->setCondition($conditionQuery, $conditionParams);
            $list->load();

            $paginationData = $this->helperPaginator($paginator, $list, $page, $limit);
            $data = array_merge(
                [
                    'data' => []
                ],
                $paginationData,
            );

            foreach($list as $item)
            {
                $data['data'][] = self::listingResponse($item);
            }

            return $this->sendResponse($data);

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * @Route("/detail", name="api_asset_detail", methods={"GET"}, options={"expose"=true})
     *
     * {mô tả api}
     *
     * @param Cache $cache
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function detailAction(
        Request $request,
        PaginatorInterface $paginator): JsonResponse
    {
        try {
            $this->setLocaleRequest();
            $condition = [
                'id' => 'required',
            ];

            $errorMessages = $this->validator->validate($condition, $request);
            if ($errorMessages) return $this->sendError($errorMessages);

            $id = $request->get('id');
            $item = Asset::getById($id);
            if ($item) {
                if ($item->getType() != 'folder') {
                    $data = self::detailResponse($request, $item);
                    return $this->sendResponse($data);
                }
                return $this->sendError('Type asset invalid');
            }
            return $this->sendError('Asset not found');

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }


    // Trả ra dữ liệu
    public function listingResponse($item)
    {
        $fomat = '';
        if ($item->getMimeType()) {
            $fomat = explode('/', $item->getMimeType());
            $fomat =  $fomat[1];
        }

        $publicURL = AssetServices::getThumbnailPath($item);
        $filenName = "<div class='div-images'> <div class='tableCell--titleThumbnail preview--image d-flex align-center'>"
            . "<img class='me-2 image-default' src=' " .  $publicURL . "'><span>" .
            $item->getFileName() .
            "</span></div>" . "<div class='image-preview'> <img src='" . $publicURL . "' alt='Preview Image'></div> </div>";

        $json = [
            'id' => $item->getId(),
            'file' => $item->getFileName(),
            'fileName' => ($item->getType() == "image") ? $filenName : "<div class='tableCell--titleThumbnail d-flex align-center'><img class='me-2' src=' " .  $publicURL . "'><span>" .  $item->getFileName() . "</span></div>",
            'creationDate' => ($item->getType() != "folder") ? self::getTimeAgo($item->getCreationDate()) : '',
            'size' => ($item->getType() != "folder") ? round((int)$item->getFileSize() / (1024 * 1024), 2) . "MB" : '',
            'parenId' => $item->getParent()?->getId(),
            'type' => ($item->getType() == "folder") ? 'folder' : $item->getType(),
            'fomat' => $fomat,
            'publicURL' => $publicURL,
            'urlDownload' => $item->getPath() . $item->getFileName(),
            'showPreview' =>  false,
            'previewURL' => $publicURL
        ];

        return $json;
    }

    public function detailResponse($request, $item) 
    {
        $languages = \Pimcore\Tool::getValidLanguages();
        $domain = $_SERVER['HTTP_HOST'];
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $domain = $protocol . $_SERVER['HTTP_HOST'];

        $fomat = '';
        if ($item->getMimeType()) {
            $fomat = explode('/', $item->getMimeType());
            $fomat =  $fomat[1];
        }

        $width = 0;
        $height = 0;
        if ($item->getType() == 'image') {
            $width = $item->getWidth();
            $height = $item->getHeight();
        }

        $language = $languages[0];
        if ($request->get('_locale')) {
            $language = $request->get('_locale');
        }
        $alt = '';
        $caption = '';
        $description = '';
        $videoMov = '';
        $videoWebm = '';

        $metaData = $item->getMetaData();
        foreach ($metaData as $item) {
            if (($item['name'] == 'alt') && ($item['language'] == $language)) {
                $alt = $item['data'];
            }
            if (($item['name'] == 'caption') && ($item['language'] == $language)) {
                $caption = $item['data'];
            }
            if (($item['name'] == 'description') && ($item['language'] == $language)) {
                $description = $item['data'];
            }
            if (($item['name'] == 'mov') && ($item['language'] == $language)) {
                $videoMov = $item['data']->getPath() . $item['data']->getFileName();
            }
            if (($item['name'] == 'webm') && ($item['language'] == $language)) {
                $videoWebm = $item['data']->getPath() . $item['data']->getFileName();
            }
        }

        $json = [
            'id' => $item->getId(),
            'fileName' => $item->getFileName(),
            'size' => round((int)$item->getFileSize() / (1024 * 1024), 3) . " MB",
            'fomat' => $fomat,
            'type' => $item->getType(),
            'width' => $width,
            'dimensions' => $width . " x " . $height,
            'uploadOn' => date("M j, Y  H:i", $item->getModificationDate()),
            'publicURL' => $domain . $item->getPath() . $item->getFileName(),
            'path' =>  $item->getPath() . $item->getFileName(),
            'data' => ($item->getType() == 'text') ? $item->getData() : '',
            'alt' => $alt,
            'caption' => $caption,
            'description' => $description,
            'videoMov' => $videoMov,
            'videoWebm' => $videoWebm,
            'languages' => $languages,
            'parentId' => $item->getParentId(),
            'publish' => round((int)$item->getFileSize() / (1024 * 1024), 3) == 0,
            'mimetype' => $item->getMimetype(),
        ];

        return $json;
    }
}