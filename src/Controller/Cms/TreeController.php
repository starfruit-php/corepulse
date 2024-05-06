<?php

namespace CorepulseBundle\Controller\Cms;

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
use CorepulseBundle\Controller\Cms\ObjectController;

class TreeController extends BaseController
{
    const relationField = [
        "manyToOneRelation"
    ];

    const relationsField = ["manyToManyObjectRelation", "manyToManyRelation", "advancedManyToManyRelation", "advancedmanyToManyObjectRelation"];

    /**
     * @Route("/catalog", name="cms_catalog", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function catalogAction(Request $request)
    {
        $tree = $request->get('tree');
        $table = $request->get('table');

        $options = SearchHelper::getClassSearch(null);

        return $this->renderWithInertia('Pages/Catalog/Layout', [
            'options' => $options,
            'tree' => $tree,
            'table' => $table
        ]);
    }

    /**
     * @Route("/tree-create", name="tree_create", methods={"POST"}, options={"expose"=true}))
     */
    public function treeCreateAction(Request $request)
    {
        $status = true;
        $message = '';
        $type = $request->get('type');
        $id = $request->get('id');
        $key = $request->get('key');
        $className = $request->get('className');

        $root = DataObject::getById($id);

        return new JsonResponse([
            'status' => $status,
            'message' => $message,
        ]);
    }

    /**
     * @Route("/tree-object-children", name="tree_object_children", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function treeObjectChildrenAction(Request $request)
    {
        $parentId = $request->get('parentId');
        $className = $request->get('className');

        $datas = [
            'nodes' => [],
            'children' => [],
        ];

        $conditions = '`parentId` = ? AND (`className` = ? OR `className` IS NULL)';
        $params = [ $parentId, $className ];

        $listing = new DataObject\Listing();
        $listing->setCondition($conditions, $params);
        $listing->setUnpublished(true);
        $listing->setObjectTypes([DataObject::OBJECT_TYPE_FOLDER, DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_VARIANT]);

        foreach ($listing as $item) {
            $datas['children'][] = (string)$item->getId();
        }

        return new JsonResponse($datas);
    }

    /**
     * @Route("/tree-object-listing", name="tree_object_listing", methods={"POST"}, options={"expose"=true}))
     */
    public function treeObjectListingAction(Request $request)
    {
        $datas = [];
        $parents = [];
        $checkTree = [];

        $className = $request->get('className');
        $checked = $request->get('checked');

        if ($className) {
            $modelName = "Pimcore\\Model\\DataObject\\" . ucfirst($className) . '\Listing';
            $listing = new $modelName();
            $listing->setUnpublished(true);
            $listing->setObjectTypes([DataObject::OBJECT_TYPE_FOLDER, DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_VARIANT]);

            $folderListing = new DataObject\Listing();
            $folderListing->setObjectTypes([DataObject::OBJECT_TYPE_FOLDER]);
            // foreach ($folderListing as $item) {
            //     $data = TreeHelper::getObjectItemJson($item);

            //     $datas['nodes'][(string)$item->getId()] = $data;
            //     $checkTree[] = $item->getId();
            // }

            foreach ($listing as $item) {
                $parent = $item->getParent();
                if ($parent instanceof DataObject\Folder) {
                    $parentId = $parent->getId();

                    if (!in_array($parentId, $parents)) {
                        $parents[] = (string)$parentId;
                        $data = TreeHelper::getObjectItemJson($parent);

                        $datas['nodes'][(string)$parentId] = $data;
                    }
                }
                $data = TreeHelper::getObjectItemJson($item);

                $datas['nodes'][(string)$item->getId()] = $data;
                $checkTree[] = $item->getId();
            }
        }

        // $parentFolder = $this->getParentFolder($datas['nodes'], $parents);

        // foreach ($parentFolder as $parent) {
        //     if (isset($datas['nodes'][$parent])) {
        //         $children = $datas['nodes'][$parent]['children'];
        //         if (!empty($children)) {
        //             $datas['nodes'][$parent]['state'] = [
        //                 'opened' => true,
        //             ];
        //         }
        //     }
        // }

        if (!$checked) {
            $datas['config'] = [
                // 'roots' => $datas['nodes'][1]['children'],
                'roots' => $parents,
                'keyboardNavigation' => false,
                'dragAndDrop' => false,
                'checkboxes' => true,
                'editable' => false,
                'disabled' => false,
                'padding' => 35,
            ];
        }

        $datas['checkTree'] =  $checkTree;

        return new JsonResponse($datas);
    }

    public function getParentFolder($data, $parents) {
        $result = $parents;
        $newParents = $parents;

        while (!empty($newParents)) {
            $currentParent = array_shift($newParents);

            if (isset($data[$currentParent])) {
                $parentId = $data[$currentParent]['parentId'];

                if (!in_array($parentId, $result)) {
                    $result[] = (string)$parentId;
                    $newParents[] = (string)$parentId;
                }
            }
        }

        return $result;
    }

    /**
     * @Route("/tree-object-table", name="tree_object_table", methods={"POST"}, options={"expose"=true}))
     */
    public function treeObjectTableAction(Request $request, PaginatorInterface $paginator)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 25);
        $order = $request->get('order', 'asc');
        $orderKey = $request->get('orderKey', 'parentId');
        $root = $request->get('root', 'DataObject');
        $parentId = $request->get('parentId');
        $className = $request->get('className');
        $classId = $request->get('classId');

        $fieldKey = $request->get('fieldKey');
        $fieldData = $request->get('fieldData');

        $classDefinition = ClassDefinition::getById($classId);

        $conditions = '';
        $params = [];
        $dataListing = [];
        $totalItems = 0;
        $relation = [];
        $relations = [];

        if ($fieldKey && $fieldData) {
            $checkRelation  = substr($fieldKey, -4) === "__id";
            if ($checkRelation) {
                foreach ($fieldData as $data) {
                    $conditions .= " `" . $fieldKey . "` = ? OR";
                    $params[] = $data;
                }

                $conditions = rtrim($conditions, ' OR ');
            } else {
                foreach ($fieldData as $data) {
                    $conditions .= " `" . $fieldKey . "` LIKE ? OR";
                    $params[] = '%' . $data . '%';
                }

                $conditions = rtrim($conditions, ' OR ');
            }
        }

        if ($className && $classDefinition) {
            $fields = $classDefinition->getFieldDefinitions();
            foreach ($fields as $key => $field) {
                if (in_array($field->getFieldtype(), self::relationField)) {
                    $relation[] = $key;
                }

                if (in_array($field->getFieldtype(), self::relationsField)) {
                    $relations[] = $key;
                }
            }

            $modelName = "Pimcore\\Model\\DataObject\\" . ucfirst($className) . '\Listing';
            $listing = new $modelName();
            $listing->setUnpublished(true);
            $listing->setObjectTypes([DataObject::OBJECT_TYPE_FOLDER, DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_VARIANT]);

            $listing->setCondition($conditions, $params);
            $listing->setOrderKey($orderKey);
            $listing->setOrder($order);

            $listing = $paginator->paginate(
                $listing,
                $page,
                $limit
            );

            $totalItems = $listing->getPaginationData()["totalCount"];

            foreach ($listing as $item) {
                $data = TreeHelper::getItemJson($item, $root);
                if (count($relation)) {
                    foreach ($relation as $key) {
                        $getValue = 'get' . ucfirst($key);
                        $value = $item->$getValue();
                        if ($value) {
                            $data[$key . '__id'] = $value->getKey();
                        }
                    }
                }

                if (count($relations)) {
                    foreach ($relations as $key) {
                        $getValue = 'get' . ucfirst($key);
                        $value = $item->$getValue();
                        $dataValue = [];
                        if (is_array($value) && count($value)) {
                            foreach ($value as $k => $v) {
                                $dataValue[] = $v->getKey();
                            }
                        }
                        $data[$key] = $dataValue;
                    }
                }
                $dataListing[] = $data;
            }
        }

        return new JsonResponse([
            'listing' => $dataListing,
            'limit' => $limit,
            'totalItems' => $totalItems,
        ]);
    }

    /**
     * @Route("/tree-object-field-relation", name="tree_object_field_relation", methods={"POST"}, options={"expose"=true}))
     */
    public function treeObjectFieldRelationAction(Request $request)
    {
        $datas = [];

        $classId = $request->get('classId');
        $classDefinition = ClassDefinition::getById($classId);
        if ($classDefinition) {
            $fields = $classDefinition->getFieldDefinitions();
            foreach ($fields as $key => $field) {
                if (in_array($field->getFieldtype(), self::relationField)) {
                    $datas[] = [
                        'key' => $key,
                        'value' => $key . '__id',
                    ];
                }

                if (in_array($field->getFieldtype(), self::relationsField)) {
                    $datas[] = [
                        'key' => $key,
                        'value' => $key,
                    ];
                }
            }
        }

        return new JsonResponse($datas);
    }

    /**
     * @Route("/tree-object-field", name="tree_object_field", methods={"POST"}, options={"expose"=true}))
     */
    public function treeObjectFieldAction(Request $request)
    {
        $datas = [
            [
                'key' => 'id',
                'title' => 'Id',
                'removable' => true,
            ],
            [
                'key' => 'parentId',
                'title' => 'Parent Id',
                'removable' => true,
            ],
            [
                'key' => 'key',
                'title' => 'Key',
                'removable' => true,
            ],
            [
                'key' => 'type',
                'title' => 'Type',
                'removable' => true,
            ],
            [
                'key' => 'published',
                'title' => 'Published',
                'removable' => true,
            ],
            [
                'key' => 'className',
                'title' => 'Class Name',
                'removable' => true,
            ],
            [
                'key' => 'path',
                'title' => 'Full Path',
                'removable' => true,
            ],
        ];

        $classId = $request->get('classId');
        $classDefinition = ClassDefinition::getById($classId);
        if ($classDefinition) {
            $fields = $classDefinition->getFieldDefinitions();

            foreach ($fields as $key => $field) {
                if (in_array($field->getFieldtype(), self::relationField)) {
                    $datas[] = [
                        'key'  => $key . '__id',
                        'title' => $key,
                        'removable' => true,
                    ];
                }

                if (in_array($field->getFieldtype(), self::relationsField)) {
                    $datas[] = [
                        'key' => $key,
                        'title' => $key,
                        'removable' => true,
                    ];
                }
            }
        }

        return new JsonResponse($datas);
    }

    /**
     * @Route("/tree-table", name="tree_table", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function treeTableAction(Request $request, PaginatorInterface $paginator)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 25);
        $order = $request->get('order', 'asc');
        $orderKey = $request->get('orderKey', 'parentId');
        $root = $request->get('root');
        $parentId = $request->get('parentId');
        $classId = $request->get('classId');

        $conditions = '`id` > 1';
        $params = [];
        $dataListing = [];
        $totalItems = 0;

        if (TreeHelper::checkRoot($root)) {
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

            if ($root == 'DataObject' && $classId) {
                $conditions .= ' AND `classId` = ?';
                $params[] = $classId;
            }

            $listing = TreeHelper::getListing($root);
            $listing->setCondition($conditions, $params);
            $listing->setOrderKey($orderKey);
            $listing->setOrder($order);

            $listing = $paginator->paginate(
                $listing,
                $page,
                $limit
            );

            $totalItems = $listing->getPaginationData()["totalCount"];

            foreach ($listing as $item) {
                $data = TreeHelper::getItemJson($item, $root);

                $dataListing[] = $data;
            }
        }

        return new JsonResponse([
            'listing' => $dataListing,
            'limit' => $limit,
            'totalItems' => $totalItems,
        ]);
    }

    /**
     * @Route("/tree-listing", name="tree_listing", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function treeListingAction(Request $request)
    {
        $datas = [];
        $listing = [];
        $root = $request->get('root');

        if (TreeHelper::checkRoot($root)) {
            $listing = TreeHelper::getListing($root);

            $listing->setCondition('`parentId` = 0 OR `parentId` = 1');

            foreach ($listing as $item) {
                $data = TreeHelper::getTreeItemJson($item, $root);

                $datas['nodes'][(string)$item->getId()] = $data;
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
        }

        return new JsonResponse($datas);
    }

    /**
     * @Route("/tree-children", name="tree_children", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function treeChildrenAction(Request $request)
    {
        $id = $request->get('id');
        $config = $request->get('config');
        $root = $request->get('root');

        $datas = [
            'nodes' => [],
            'children' => [],
        ];

        $conditions = '`parentId` = ?';
        $params = [ $id ];

        if($config && $root == 'DataObject') {
            $conditions .= ' AND (';

            foreach($config as $key) {
                $conditions .= ' `classId` = ? OR ';
                $params[] = $key;
            }

            $conditions .= ' `classId` IS NULL)';
        }

        if (TreeHelper::checkRoot($root)) {
            $listing = TreeHelper::getListing($root);

            $listing->setCondition($conditions, $params);

            foreach ($listing as $item) {
                $data = TreeHelper::getTreeItemJson($item, $root);

                $datas['nodes'][(string)$item->getId()] = $data;
                $datas['children'][] = (string)$item->getId();
            }
        }

        return new JsonResponse($datas);
    }

    /**
     * @Route("/tree-update", name="tree_update", methods={"GET", "POST"}, options={"expose"=true}))
     */
    public function treeUpdateAction(Request $request)
    {
        $root = $request->get('root');
        $parent = $request->get('parent');
        $id = $request->get('id');

        $status = [];
        try {
            if (TreeHelper::checkRoot($root)) {
                $modelName = 'Pimcore\\Model\\' . $root;

                $model = $modelName::getById($id);
                $model->setParentId($parent);
                $model->save();

                $status = ['status' => true];
            } else $status = [
                'status' => false,
                'message' => 'root is valid'
            ];

        } catch (\Throwable $th) {
            $status = [
                'status' => false,
                'message' => $th->getMessage()
            ];
        }

        return new JsonResponse($status);
    }
}
