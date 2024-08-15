<?php

namespace CorepulseBundle\Controller\Api;

use CorepulseBundle\Services\ClassServices;
use CorepulseBundle\Services\DataObjectServices;
use Pimcore\Db;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation;

/**
 * @Route("/object")
 */
class ObjectController extends BaseController
{
    private array $objectData = [];

    private array $objectLayoutData = [];

    private array $metaData = [];

    /**
     * @Route("/submit-column-setting", name="api_object_submit_column_setting", methods={"POST"}, options={"expose"=true})
     */
    public function submitColumnSetting()
    {
        try {
            $condition = [
                'id' => 'required',
                'columns' => 'required|array',
            ];
            $messageError = $this->validator->validate($condition, $this->request);
            if($messageError) return $this->sendError($messageError);

            $classId = $this->request->get("id");
            $columns = $this->request->get('columns');

            $update = ClassServices::updateTable($classId, $columns, true);

            $data = [
                'success' => true,
                'message' => 'class update table view success'
            ];
            return $this->sendResponse($data);
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), 500);
        }
    }

    /**
     * @Route("/get-column-setting", name="api_object_get_column_setting", methods={"GET"}, options={"expose"=true})
     */
    public function getColumnSetting()
    {
        try {
            $condition = [
                'id' => 'required',
            ];
            $messageError = $this->validator->validate($condition, $this->request);
            if($messageError) return $this->sendError($messageError);

            $classId = $this->request->get("id");

            $checkClass = ClassServices::isValid($classId);

            if (!$checkClass) {
                return $this->sendError([
                    'success' => false,
                    'message' => 'Class not found.'
                ], 500);
            }

            $classConfig = ClassServices::getConfig($classId);
            $visibleFields = json_decode($classConfig['visibleFields'], true);

            $fields = $visibleFields['fields'];
            $columns = array_merge(ClassServices::systemField($visibleFields), $fields);
            $tableView = $visibleFields['tableView'];

            $visibleGridView = ClassServices::filterFill($columns, $tableView);

            $data = [
                'columns' => $columns,
                'visibleGridView' => $visibleGridView,
            ];

            return $this->sendResponse($data);
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), 500);
        }
    }

    /**
     * @Route("/listing-by-object", name="api_object_listing", methods={"GET"}, options={"expose"=true})
     */
    public function listingByObject()
    {
        try {
            $orderByOptions = ['modificationDate'];
            $conditions = $this->getPaginationConditions($this->request, $orderByOptions);
            list($page, $limit, $condition) = $conditions;

            $condition = array_merge($condition, [
                'id' => 'required',
                'search' => '',
                'columns' => 'array',
            ]);
            $messageError = $this->validator->validate($condition, $this->request);
            if($messageError) return $this->sendError($messageError);

            $classId = $this->request->get("id");

            $checkClass = ClassServices::isValid($classId);

            $classConfig = ClassServices::getConfig($classId);
            $visibleFields = json_decode($classConfig['visibleFields'], true);

            $columns = array_merge(ClassServices::systemField($visibleFields), $visibleFields['fields']);
            $fields = $this->request->get('columns') ? ClassServices::filterFill($columns, $this->request->get('columns')) : $columns;

            $className = $visibleFields['class'];
            if (!$checkClass || !$className || !class_exists('\\Pimcore\\Model\\DataObject\\' . ucfirst($className))) {
                return $this->sendError([
                    'success' => false,
                    'message' => 'Class not found.'
                ], 500);
            }

            $listing = call_user_func_array('\\Pimcore\\Model\\DataObject\\' . $className . '::getList', [["unpublished" => true]]);

            $search = $this->request->get('search');
            $locale = $this->request->get('locale', \Pimcore\Tool::getDefaultLanguage());

            if ($search) {
                $search = json_decode($search, true);
                $search = array_filter($search, function ($value) {
                    return !($value === "" || $value === null);
                });
                if (count($search)) {
                    foreach ($search as $field => $keyword) {
                        if ($field == "published") {
                            continue;
                        }
                        if ($field == "nameObject") {
                            $listing->addConditionParam("LOWER(`key`) likee LOWER('%" . $keyword . "%')");
                            continue;
                        }
                        if (is_array($keyword)) {
                            // if (count($relation) && in_array($field, $relation)) {
                            //     $count = count($keyword);
                            //     $value = $keyword[$count - 1];
                            //     $listing->addConditionParam("`" . $field . "__id` = '" . $value . "'");
                            //     continue;
                            // }

                            // if (count($relations) && in_array($field, $relations)) {
                            //     $count = count($keyword);
                            //     $value = $keyword[$count - 1];
                            //     $listing->addConditionParam("`" . $field . "` like '%" . $value . "%'");
                            //     continue;
                            // }

                            if (array_key_exists('type', $keyword)) {
                                if ($keyword['type'] == "range") {
                                    $listing->addConditionParam("`" . $keyword['key'] . "` >= '" . $keyword['from'] . "' AND `" . $keyword['key'] . "` <= '" . $keyword['to'] . "'");
                                    continue;
                                }

                                if ($keyword['type'] == "picker") {
                                    continue;
                                }
                            }

                            continue;
                        } else {
                            $listing->addConditionParam("LOWER(`" . $field . "`) like LOWER('%" . $keyword . "%')");
                        }
                    }
                }
            }

            $listing->setLocale($locale);
            $listing->setUnpublished(true);

            if ($limit == -1) {
                $limit = 10000;
            }

            $pagination = $this->paginator($listing, $page, $limit);

            $data = [
                'paginationData' => $pagination->getPaginationData(),
            ];

            foreach($pagination as $item) {
                $data['data'][] =  DataObjectServices::getData($item, $fields);
            }

            return $this->sendResponse($data);

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * @Route("/detail/{id}", name="api_object_detail", methods={"GET", "POST"})
     */
    public function detailAction()
    {
        try {
            $condition = [
                'id' => 'required',
            ];
            $messageError = $this->validator->validate($condition, $this->request);
            if($messageError) return $this->sendError($messageError);

            $id = $this->request->get('id');
            $objectFromDatabase = DataObject\Concrete::getById($id);

            if (!$objectFromDatabase) return $this->sendError('Object not found', 500);

            $objectFromDatabase = clone $objectFromDatabase;

            // set the latest available version for editmode
            $draftVersion = null;
            $object = $this->getLatestVersion($objectFromDatabase, $draftVersion);

            $objectFromVersion = $object !== $objectFromDatabase;

            $objectData = [];

            if ($draftVersion && $objectFromDatabase->getModificationDate() < $draftVersion->getDate()) {
                $objectData['draft'] = [
                    'id' => $draftVersion->getId(),
                    'modificationDate' => $draftVersion->getDate(),
                    'isAutoSave' => $draftVersion->isAutoSave(),
                ];
            }

            try {
                $this->getDataForObject($object, $objectFromVersion);
            } catch (\Throwable $e) {
                $object = $objectFromDatabase;
                $this->getDataForObject($object, false);
            }

            // $objectData['data'] = $this->objectData;
            $objectData['metaData'] = $this->metaData;
            $layout = DataObject\Service::getSuperLayoutDefinition($object);
            $objectData['layout'] = $this->getObjectVarsRecursive($object, $layout);
            $objectData['layoutData'] = $this->objectLayoutData;
            $objectData['sidebar'] = DataObjectServices::getSidebarData($object);

            return $this->sendResponse($objectData);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

   /**
     * @Route("/get-sidebar", name="api_object_slider_bar", methods={"GET"}, options={"expose"=true})
     *
     * {mô tả api}
     *
     * @param Cache $cache
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getSidebar(
        Request $request,
        PaginatorInterface $paginator): JsonResponse
    {
        try {
            $objectSetting = Db::get()->fetchAssociative('SELECT * FROM `vuetify_settings` WHERE `type` = "object"', []);

            $data['data'] = [];
            if ($objectSetting !== null && $objectSetting) {
                $query = 'SELECT * FROM `classes`';
                $classListing = Db::get()->fetchAllAssociative($query);
                $dataObjectSetting = json_decode($objectSetting['config']) ?? [];
                $data = [];
                foreach ($classListing as $class) {
                    if (in_array($class['id'], $dataObjectSetting)) {
                        $classDefinition = DataObject\ClassDefinition::getById($class['id']);

                        $newData["id"] = $class["id"];
                        $newData["name"] = $class["name"];
                        $newData["title"] = $classDefinition ? ($classDefinition->getTitle() ? $classDefinition->getTitle() :  $classDefinition->getName()) : $class["name"];

                        $data['data'][] = $newData;
                    }
                }
            }

            return $this->sendResponse($data);

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    protected function getObjectVarsRecursive($object, $layout)
    {
        $vars = get_object_vars($layout);

        if (method_exists($layout, 'getFieldType')) {
            $vars['fieldtype'] = $layout->getFieldType();

            $data  = null;
            $getClass = '\\CorepulseBundle\\Component\\Field\\' . ucfirst($vars['fieldtype']);
            if (class_exists($getClass)) {
                $value = new $getClass($object, $vars);
                $data = $value->getValue();
                $this->objectLayoutData[$vars['name']] = $data;
            }
        }

        if (isset($vars['children'])) {
            foreach ($vars['children'] as $key => $value) {
                if (is_object($value)) {
                    $vars['children'][$key] = $this->getObjectVarsRecursive($object, $value);
                }
            }
        }

        return $vars;
    }

    protected function getLatestVersion(DataObject\Concrete $object,  ? DataObject\Concrete &$draftVersion = null) : DataObject\Concrete
    {
        $latestVersion = $object->getLatestVersion();
        if ($latestVersion) {
            $latestObj = $latestVersion->loadData();
            if ($latestObj instanceof DataObject\Concrete) {
                $draftVersion = $latestVersion;

                return $latestObj;
            }
        }

        return $object;
    }

    private function getDataForObject(DataObject\Concrete $object, bool $objectFromVersion = false): void
    {
        foreach ($object->getClass()->getFieldDefinitions(['object' => $object]) as $key => $def) {
            $this->getDataForField($object, $key, $def, $objectFromVersion);
        }
    }

    /**
     * Gets recursively attribute data from parent and fills objectData and metaData
     */
    private function getDataForField(DataObject\Concrete $object, string $key, DataObject\ClassDefinition\Data $fielddefinition, bool $objectFromVersion, int $level = 0): void
    {
        $parent = DataObject\Service::hasInheritableParentObject($object);

        $getter = 'get' . ucfirst($key);

        // Editmode optimization for lazy loaded relations (note that this is just for AbstractRelations, not for all
        // LazyLoadingSupportInterface types. It tries to optimize fetching the data needed for the editmode without
        // loading the entire target element.
        // ReverseObjectRelation should go in there anyway (regardless if it a version or not),
        // so that the values can be loaded.
        if (
            (!$objectFromVersion && $fielddefinition instanceof AbstractRelations)
            || $fielddefinition instanceof ReverseObjectRelation
        ) {
            $refId = null;

            if ($fielddefinition instanceof ReverseObjectRelation) {
                $refKey = $fielddefinition->getOwnerFieldName();
                $refClass = DataObject\ClassDefinition::getByName($fielddefinition->getOwnerClassName());
                if ($refClass) {
                    $refId = $refClass->getId();
                }
            } else {
                $refKey = $key;
            }

            $relations = $object->getRelationData($refKey, !$fielddefinition instanceof ReverseObjectRelation, $refId);

            if ($fielddefinition->supportsInheritance() && empty($relations) && !empty($parent)) {
                $this->getDataForField($parent, $key, $fielddefinition, $objectFromVersion, $level + 1);
            } else {
                $data = [];

                if ($fielddefinition instanceof DataObject\ClassDefinition\Data\ManyToOneRelation) {
                    if (isset($relations[0])) {
                        $data = $relations[0];
                        $data['published'] = (bool) $data['published'];
                    } else {
                        $data = null;
                    }
                } elseif (
                    ($fielddefinition instanceof DataObject\ClassDefinition\Data\OptimizedAdminLoadingInterface && $fielddefinition->isOptimizedAdminLoading())
                    || ($fielddefinition instanceof ManyToManyObjectRelation && !$fielddefinition->getVisibleFields() && !$fielddefinition instanceof DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation)
                ) {
                    foreach ($relations as $rkey => $rel) {
                        $index = $rkey + 1;
                        $rel['fullpath'] = $rel['path'];
                        $rel['classname'] = $rel['subtype'];
                        $rel['rowId'] = $rel['id'] . AbstractRelations::RELATION_ID_SEPARATOR . $index . AbstractRelations::RELATION_ID_SEPARATOR . $rel['type'];
                        $rel['published'] = (bool) $rel['published'];
                        $data[] = $rel;
                    }
                } else {
                    $fieldData = $object->$getter();
                    $data = $fielddefinition->getDataForEditmode($fieldData, $object, ['objectFromVersion' => $objectFromVersion]);
                }
                $this->objectData[$key] = $data;
                $this->metaData[$key]['objectid'] = $object->getId();
                $this->metaData[$key]['inherited'] = $level != 0;
            }
        } else {
            $fieldData = $object->$getter();
            $isInheritedValue = false;

            if ($fielddefinition instanceof DataObject\ClassDefinition\Data\CalculatedValue) {
                $fieldData = new DataObject\Data\CalculatedValue($fielddefinition->getName());
                $fieldData->setContextualData('object', null, null, null, null, null, $fielddefinition);
                $value = $fielddefinition->getDataForEditmode($fieldData, $object, ['objectFromVersion' => $objectFromVersion]);
            } else {
                $value = $fielddefinition->getDataForEditmode($fieldData, $object, ['objectFromVersion' => $objectFromVersion]);
            }

            // following some exceptions for special data types (localizedfields, objectbricks)
            if ($value && ($fieldData instanceof DataObject\Localizedfield || $fieldData instanceof DataObject\Classificationstore)) {
                // make sure that the localized field participates in the inheritance detection process
                $isInheritedValue = $value['inherited'];
            }
            if ($fielddefinition instanceof DataObject\ClassDefinition\Data\Objectbricks && is_array($value)) {
                // make sure that the objectbricks participate in the inheritance detection process
                foreach ($value as $singleBrickData) {
                    if (!empty($singleBrickData['inherited'])) {
                        $isInheritedValue = true;
                    }
                }
            }

            if ($fielddefinition->isEmpty($fieldData) && !empty($parent)) {
                $this->getDataForField($parent, $key, $fielddefinition, $objectFromVersion, $level + 1);
                // exception for classification store. if there are no items then it is empty by definition.
                // consequence is that we have to preserve the metadata information
                // see https://github.com/pimcore/pimcore/issues/9329
                if ($fielddefinition instanceof DataObject\ClassDefinition\Data\Classificationstore && $level == 0) {
                    $this->objectData[$key]['metaData'] = $value['metaData'] ?? [];
                    $this->objectData[$key]['inherited'] = true;
                }
            } else {
                $isInheritedValue = $isInheritedValue || ($level != 0);
                $this->metaData[$key]['objectid'] = $object->getId();

                $this->objectData[$key] = $value;
                $this->metaData[$key]['inherited'] = $isInheritedValue;

                if ($isInheritedValue && !$fielddefinition->isEmpty($fieldData) && !$fielddefinition->supportsInheritance()) {
                    $this->objectData[$key] = null;
                    $this->metaData[$key]['inherited'] = false;
                    $this->metaData[$key]['hasParentValue'] = true;
                }
            }
        }
    }
}
