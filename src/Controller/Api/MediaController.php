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
use CorepulseBundle\Services\Helper\SearchHelper;


/**
 * @Route("/media")
 */
class MediaController extends BaseController
{
    /**
     * @Route("/get-asset", name="api_get_asset", methods={"GET"}, options={"expose"=true})
     *
     * {mô tả api}
     *
     * @param Cache $cache
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getAssetAction(
        Request $request,
        PaginatorInterface $paginator
    ): JsonResponse {
        try {
            $orderByOptions = ['mimetype'];
            $conditions = $this->getPaginationConditions($request, $orderByOptions);
            list($page, $limit, $condition) = $conditions;

            $condition = array_merge($condition, [
                'id' => '',
                'types' => '',
                'search' => '',
                'path' => '',
            ]);

            $messageError = $this->validator->validate($condition, $request);
            if ($messageError) return $this->sendError($messageError);

            $order_by = $request->get('order_by') ? $request->get('order_by') : 'mimetype';
            $order = $request->get('order') ? $request->get('order') : 'ASC';

            $parentId = $request->get('id') ? $request->get('id') : '1';
            $types = $request->get('types');
            $search = $request->get('search');
            $pathFolder = $request->get('path');

            if ($pathFolder) {
                $item = Asset::getByPath($pathFolder);
                if ($item) {
                    $parentId = $item->getId();
                }
            }

            $conditionQuery = 'id != 1 AND parentId = :parentId';
            $conditionParams['parentId'] = $parentId;

            if ($types && ($types != "undefined")) {
                $types = explode(',', $types);
                $conditionTypes = '';
                for ($i = 0; $i < count($types); $i++) {
                    $or = " OR ";
                    if ($i == (count($types) - 1)) {
                        $or = '';
                    }
                    $conditionName = "type = '" . $types[$i] . "'" . $or;
                    $conditionTypes .= $conditionName;
                }
                $conditionQuery .= ' AND (' . $conditionTypes . ')';
            }

            if (isset($search) && !empty($search) && ($search != "undefined")) {
                $conditionQuery .= ' AND LOWER(filename) LIKE LOWER(:search) AND type != "folder"';
                $conditionParams['search'] = '%' . $search . '%';
            }

            $listingAsset = new \Pimcore\Model\Asset\Listing();
            $listingAsset->setCondition($conditionQuery, $conditionParams);
            $listingAsset->setOrderKey($order_by);
            $listingAsset->setOrder($order);

            $images = [];

            $paginationData = $this->helperPaginator($paginator, $listingAsset, $page, $limit);
            $data = array_merge(
                [
                    'data' => []
                ],
                $paginationData,
            );

            foreach ($listingAsset as $item) {
                if ($item->getType() != "folder") {
                    $publicURL = AssetServices::getThumbnailPath($item);
                    $data['data'][] = [
                        'id' => $item->getId(),
                        'type' => $item->getType(),
                        'mimetype' => $item->getMimetype(),
                        'name' => $item->getFileName(),
                        'fullPath' => $publicURL,
                        'parentId' => $item->getParentId(),
                        'checked' => false,
                        'path' => $item->getFullPath(),
                    ];
                }
            }

            return $this->sendResponse($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * @Route("/tree-listing-asset", name="api_tree_listing_asset", methods={"GET"}, options={"expose"=true})
     *
     * {mô tả api}
     *
     * @param Cache $cache
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function treeListingAction(
        Request $request,
        PaginatorInterface $paginator
    ): JsonResponse {
        try {
            $datas['data'] = [];

            $listing = new Asset\Listing();
            $listing->setCondition('`parentId` = 0 OR `parentId` = 1 AND type = "folder"');
            $listing->setOrderKey('mimetype');
            $listing->setOrder('ASC');

            foreach ($listing as $item) {
                $data = [];
                foreach ($item->getChildren() as $children) {
                    if ($children->getType() == "folder") {
                        $data[] = (string)$children->getId();
                    }
                }

                $publicURL = AssetServices::getThumbnailPath($item);

                if ($item->getId() == 1) {
                    $datas['data']['home'] = [
                        'id' => 1,
                        'key' =>  "Home",
                        'type' => $item->getType(),
                        'children' => [],
                        'icon' => "mdi-home",
                        'image' => "/bundles/pimcoreadmin/img/flat-color-icons/home-gray.svg",
                        'publish' => true,
                        'classId' => $item->getType() != 'folder' ? $item->getType() : 'tree-folder',
                    ];
                }

                $datas['data']['nodes'][(string)$item->getId()] = [
                    'id' => $item->getId(),
                    'key' => $item->getFileName() ? $item->getFileName() : "Home",
                    'type' => $item->getType(),
                    'children' => $data,
                    'icon' => SearchHelper::getIcon($item->getType()),
                    'image' => $publicURL,
                    'publish' => true,
                    'classId' => $item->getType() != 'folder' ? $item->getType() : 'tree-folder',
                ];
            }
            // dd($datas['data']);
            $datas['data']['config'] = [
                'roots' => $datas['data']['nodes'][1]['children'],
                'keyboardNavigation' => false,
                'dragAndDrop' => false,
                'checkboxes' => false,
                'editable' => false,
                'disabled' => false,
                'padding' => 35,
            ];

            return $this->sendResponse($datas);

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

     /**
     * @Route("/get-folder", name="api_get_folder", methods={"GET"}, options={"expose"=true})
     *
     * {mô tả api}
     *
     * @param Cache $cache
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getFolderAction(
        Request $request,
        PaginatorInterface $paginator
    ): JsonResponse {
        try {
            $orderByOptions = ['creationDate'];
            $conditions = $this->getPaginationConditions($request, $orderByOptions);
            list($page, $limit, $condition) = $conditions;

            $condition = array_merge($condition, [
                'types' => '',
            ]);
            $messageError = $this->validator->validate($condition, $request);
            if($messageError) return $this->sendError($messageError);

            $conditionQuery = 'id != 1 AND parentId = :parentId';
            $conditionParams = [
                'parentId' => 1,
            ];
    
            $checkType = $request->get('types');
            if (!$checkType) $checkType = 'image';
    
            $list = new \Pimcore\Model\Asset\Listing();
            $list->setCondition($conditionQuery, $conditionParams);
            $list->setOrderKey('creationDate');
            $list->setOrder('ASC');
            $list->load();

            $paginationData = $this->helperPaginator($paginator, $list, $page, $limit);
            $data = array_merge(
                [
                    'data' => []
                ],
                $paginationData,
            );
    
            $data['data']['folders'][] = [
                'id' => 1,
                'name' => 'Home',
                'icon' => '/bundles/pimcoreadmin/img/flat-color-icons/home-gray.svg',
            ];
            $images = [];
            foreach ($list as $item) {
                if ($item->getType() == "folder") {
                    $data['folders'][] = [
                        'id' => $item->getId(),
                        'name' => $item->getFilename(),
                        'icon' => "/bundles/pimcoreadmin/img/flat-color-icons/folder.svg",
                    ];
                } else {
                    $publicURL = AssetServices::getThumbnailPath($item);
                    $data['data']['images'][] = [
                        'id' => $item->getId(),
                        'type' => $item->getType(),
                        'mimetype' => $item->getMimetype(),
                        'name' => $item->getFileName(),
                        'fullPath' =>  $publicURL,
                        'parentId' => $item->getParentId(),
                        'path' => $item->getFullPath(),
                    ];
                }
            }

            return $this->sendResponse($data);

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * @Route("/tree-children-asset", name="api_tree_children_asset", methods={"GET"}, options={"expose"=true})
     *
     * {mô tả api}
     *
     * @param Cache $cache
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function treeChildrenDocAction(
        Request $request,
        PaginatorInterface $paginator
    ): JsonResponse {
        try {
            $orderByOptions = ['mimetype'];
            $conditions = $this->getPaginationConditions($request, $orderByOptions);
            list($page, $limit, $condition) = $conditions;

            $condition = array_merge($condition, [
                'id' => 'required',
                'config' => '',
            ]);
            $messageError = $this->validator->validate($condition, $request);
            if($messageError) return $this->sendError($messageError);

            $id = $request->get('id');
            $config = $request->get('config');

            $datas['data'] = [
                'nodes' => [],
                'children' => [],
            ];
    
            $conditions = '`parentId` = ? AND type = "folder"';
            $params = [ $id ];
    
            if($config) {
                $conditions .= ' AND (';
    
                foreach($config as $key) {
                    $conditions .= ' `classId` = ? OR ';
                    $params[] = $key;
                }
    
                $conditions .= ' `classId` IS NULL)';
            }
    
            $listing = new Asset\Listing();
            $listing->setCondition($conditions, $params);
            $listing->setOrderKey('mimetype');
            $listing->setOrder('ASC');
    
            foreach ($listing as $item) {
                $data = [];
                foreach ($item->getChildren() as $children) {
                    if ($children->getType() == 'folder') {
                        $data[] = (string)$children->getId();
                    }
                }
                $publicURL = AssetServices::getThumbnailPath($item);
                $datas['data']['nodes'][(string)$item->getId()] = [
                    'id' => $item->getId(),
                    'key' => $item->getFileName(),
                    'type' => $item->getType(),
                    'children' => $data,
                    'icon' => SearchHelper::getIcon($item->getType()),
                    'publish' => true,
                    'image' => $publicURL,
                    'classId' => $item->getType() != 'folder' ? $item->getType() : 'tree-folder',
                ];
    
                $datas['data']['children'][] = (string)$item->getId();
            }
            return $this->sendResponse($datas);

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
}
