<?php

namespace CorepulseBundle\Controller\Cms;

use CorepulseBundle\Services\DocumentServices;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pimcore\Model\DataObject;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use CorepulseBundle\Services\Helper\TreeHelper;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use CorepulseBundle\Services\Helper\SearchHelper;
use Knp\Component\Pager\PaginatorInterface;

class DocumentTreeController extends BaseController
{

    /**
     * @Route("/catalog-doc", name="cms_document_catalog", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function catalogDocAction(Request $request)
    {
        return $this->renderWithInertia('Pages/Catalog/LayoutDocument');
    }

     /**
     * @Route("/tree-table-doc", name="tree_table_doc", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function treeTableDocAction(Request $request, PaginatorInterface $paginator)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 25);
        $order = $request->get('order', 'asc');
        $orderKey = $request->get('orderKey', 'index');

        $parentId = $request->get('parentId');
        $conditions = '`id` > 1';
        $params = [];

        if ($parentId) {
            $conditions .= ' AND (';

            foreach($parentId as $key) {
                $conditions .= ' `parentId` = ? OR `id` = ? OR ';
                $params[] = $key;
                $params[] = $key;
            }

            $conditions = rtrim($conditions, ' OR ');

            $conditions .= ')';
        }

        $listing = new Document\Listing();
        $listing->setUnpublished(true);
        $listing->setCondition($conditions, $params);
        $listing->setOrderKey($orderKey);
        $listing->setOrder($order);

        $listing = $paginator->paginate(
            $listing,
            $page,
            $limit
        );

        $totalItems = $listing->getPaginationData()["totalCount"];

        $dataListing = [];

        foreach ($listing as $item) {
            $data = [
                "key" => $item->getKey(),
                "className" => $item->getType() != 'folder' ? $item->getType() : '',
                "path" => $item->getFullPath(),
                "type" => $item->getType(),
                "published" => $item->getType() != 'folder' ? ($item->getPublished() ? 'published' : 'unpublished') : '',
                "id" => $item->getId(),
                "parentId" => $item->getParentId(),
                "unSelecte" => false
            ];

            $dataListing[] = $data;
        }

        return new JsonResponse([
            'listing' => $dataListing,
            'limit' => $limit,
            'totalItems' => $totalItems,
        ]);
    }

     /**
     * @Route("/tree-listing-doc", name="tree_listing_doc", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function treeListingAction(Request $request)
    {
        $datas = [];
        $root = $request->get('root');
        $listing = new Document\Listing();
        $listing->setUnpublished(true);
        $listing->setCondition('`parentId` = 0 OR `parentId` = 1');
        $listing->setOrderKey('index');
        $listing->setOrder('ASC');

        foreach ($listing as $item) {
            $data = [];
            foreach ($item->getChildren() as $children) {
                $data[] = (string)$children->getId();
            }

            if ($item->getId() == 1) {
                $datas['home'] = [
                    'id' => 1,
                    'key' =>  "Home",
                    'type' => $item->getType(),
                    'children' => [],
                    'icon' => "mdi-home",
                    'publish' => $item->getType() != 'folder' ? $item->getPublished() : true,
                    'classId' => $item->getType() != 'folder' ? $item->getType() : 'tree-folder',
                    'modificationDate' => DocumentServices::getTimeAgo($item->getModificationDate()),
                ];
            }

            $datas['nodes'][(string)$item->getId()] = [
                'id' => $item->getId(),
                'key' => $item->getKey() ? $item->getKey() : "Home",
                'type' => $item->getType(),
                'children' => $data,
                'icon' => SearchHelper::getIcon($item->getType()),
                'publish' => $item->getType() != 'folder' ? $item->getPublished() : true,
                'classId' => $item->getType() != 'folder' ? $item->getType() : 'tree-folder',
                'modificationDate' => DocumentServices::getTimeAgo($item->getModificationDate()),
            ];
        }

        $datas['config'] = [
            'roots' => $datas['nodes'][1]['children'],
            'keyboardNavigation' => false,
            'dragAndDrop' => true,
            'checkboxes' => true,
            'editable' => false,
            'disabled' => false,
            'padding' => 35,
        ];
        // dd($datas);

        return new JsonResponse($datas);
    }

    /**
     * @Route("/tree-children-doc", name="tree_children_doc", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function treeChildrenDocAction(Request $request)
    {
        $id = $request->get('id');
        $config = $request->get('config');

        $datas = [
            'nodes' => [],
            'children' => [],
        ];

        $conditions = '`parentId` = ?';
        $params = [ $id ];

        if($config) {
            $conditions .= ' AND (';

            foreach($config as $key) {
                $conditions .= ' `classId` = ? OR ';
                $params[] = $key;
            }

            $conditions .= ' `classId` IS NULL)';
        }

        $listing = new Document\Listing();
        $listing->setUnpublished(true);
        $listing->setCondition($conditions, $params);
        $listing->setOrderKey('index');
        $listing->setOrder('ASC');

        foreach ($listing as $item) {
            $data = [];
            foreach ($item->getChildren() as $children) {
                $data[] = (string)$children->getId();
            }

            $datas['nodes'][(string)$item->getId()] = [
                'id' => $item->getId(),
                'key' => $item->getKey(),
                'type' => $item->getType(),
                'children' => $data,
                'icon' => SearchHelper::getIcon($item->getType()),
                'publish' => $item->getType() != 'folder' ? $item->getPublished() : true,
                'classId' => $item->getType() != 'folder' ? $item->getType() : 'tree-folder',
                'modificationDate' => DocumentServices::getTimeAgo($item->getModificationDate()),
            ];

            $datas['children'][] = (string)$item->getId();
        }

        return new JsonResponse($datas);
    }

    /**
     * @Route("/tree-update-doc", name="tree_update_doc", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function treeUpdateDocAction(Request $request)
    {
        $root = $request->get('root');
        $idDrop = $request->get('idDrop'); // id của item mục tiêu
        $idDrap = $request->get('idDrap'); // id của item được khéo
        $index = $request->get('index'); // biến thể hiện vị trí: -1 - kéo lên | 1 - kéo xuống | 2 - kéo vào

        $status = [];
        try { 
            if ($index) {
                if (TreeHelper::checkRoot($root)) {
                    $modelName = 'Pimcore\\Model\\' . $root;
                    $model = $modelName::getById($idDrap); // Thông tin item đc kéo
                    $itemDrop = $modelName::getById($idDrop); // thông tin item mục tiêu
                    $indexNew = (int)$model->getIndex(); //vị trí cũ của item được kéo
                    $indexNewDrop = (int)$itemDrop->getIndex(); // vị trí cũng của item mục tiêu

                    if ($index == 2) {
                        $document = new \Pimcore\Model\Document\Listing();
                        $document->setCondition('parentId = :parentId', ['parentId' => $idDrop]);

                        $totalItems = $document->count();
                        $indexNew = $totalItems ? $totalItems : 0;

                        $model->setParentId($idDrop);
                    } elseif ($index == 1) {
                        $indexNew = (int)$model->getIndex() + 1;
                        $indexNewDrop = (int)$itemDrop->getIndex() - 1;
                    } else {
                        $indexNew = (int)$model->getIndex() - 1;
                        $indexNewDrop = (int)$itemDrop->getIndex() + 1;
                    }

                    $model->setIndex($indexNew);
                    $itemDrop->setIndex($indexNewDrop);

                    $model->save();
                    $itemDrop->save();
    
                    $status = ['status' => true];
                } else $status = [
                    'status' => false,
                    'message' => 'root is valid'
                ];
            }
        } catch (\Throwable $th) {
            $status = [
                'status' => false,
                'message' => $th->getMessage()
            ];
        }

        return new JsonResponse($status);
    }

}