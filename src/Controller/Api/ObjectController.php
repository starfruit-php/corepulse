<?php

namespace CorepulseBundle\Controller\Api;

use CorepulseBundle\Controller\Cms\FieldController;
use CorepulseBundle\Services\DocumentServices;
use Pimcore\Translation\Translator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;

use DateTime;

/**
 * @Route("/object")
 */
class ObjectController extends BaseController
{
    const listField = [
        "input", "textarea", "wysiwyg", "password",
        "number", "numericRange", "slider", "numeric",
        "date", "datetime", "dateRange", "time", "manyToOneRelation",
        "select", 'multiselect', 'image', 'manyToManyRelation',
        'manyToManyObjectRelation', 'imageGallery', 'urlSlug'
    ];

    const chipField = [
        "select", "multiselect", "manyToOneRelation", "manyToManyObjectRelation",
        "manyToManyRelation", "advancedManyToManyRelation", "advancedmanyToManyObjectRelation"
    ];

    const multiField = [
        "multiselect", 'manyToManyRelation', 'manyToManyObjectRelation'
    ];

    const relationField = [
        "manyToOneRelation"
    ];

    const noSearch = ["image", "imageGallery", "urlSlug"];

    const noOrder = [
        "image", "imageGallery", "urlSlug", "multiselect", "manyToOneRelation",
        "manyToManyObjectRelation", "manyToManyRelation", "advancedManyToManyRelation", "advancedmanyToManyObjectRelation"
    ];

    const notHandleEdit = [
        "advancedManyToManyRelation",
        "advancedmanyToManyObjectRelation"
    ];

    const relationsField = ["manyToManyObjectRelation", "manyToManyRelation", "advancedManyToManyRelation", "advancedmanyToManyObjectRelation"];

    /**
     * @Route("/listing-by-object", name="api_object_listing", methods={"GET"}, options={"expose"=true})
     *
     * {mô tả api}
     *
     * @param Cache $cache
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function listingByObject(
        Request $request,
        PaginatorInterface $paginator): JsonResponse
    {
        try {
            $this->setLocaleRequest();
            $orderByOptions = ['modificationDate'];
            $conditions = $this->getPaginationConditions($request, $orderByOptions);
            list($page, $limit, $condition) = $conditions;

            $condition = array_merge($condition, [
                'object' => 'required',
                'search' => '',
            ]);
            $messageError = $this->validator->validate($condition, $request);
            if($messageError) return $this->sendError($messageError);

            $object = $request->get("object");
            $classDefinition = ClassDefinition::getById($object);

            if (class_exists('\\Pimcore\\Model\\DataObject\\' . ucfirst($classDefinition->getName()))) {
                $listing = call_user_func_array('\\Pimcore\\Model\\DataObject\\' . $classDefinition->getName() . '::getList', [["unpublished" => true]]);
            } else {
            }

            $dataJson = [
                "id" => $classDefinition->getId(),
                "name" => $classDefinition->getName(),
                "title" => $classDefinition->getTitle() ? $classDefinition->getTitle() :  $classDefinition->getName()
            ];

            $fields = $classDefinition->getFieldDefinitions();
            foreach ($fields as $key => $field) {
                if (in_array($field->getFieldtype(), self::listField)) {
                    $searchType = 'Input';
    
                    $options = [];
    
                    if (in_array($field->getFieldtype(), self::chipField)) {
                        $chips[] = $field->getTooltip() ? $field->getTooltip() : $key;
    
                        $searchType = 'Select';
                        $options = FieldController::getOptions($field->getFieldtype(), $field);
    
                        if (in_array($field->getFieldType(), self::multiField)) {
                            $multiSelect[] = $key;
                            $searchType = 'Select';
                        }
    
                        if (in_array($field->getFieldtype(), self::relationField)) {
                            $relation[] = $key;
                            $searchType = 'Relation';
                        }
    
                        if (in_array($field->getFieldtype(), self::relationsField)) {
                            $relations[] = $key;
                            $searchType = 'Relation';
                        }
                    } elseif ($field->getFieldtype() == 'dateRange') {
                        $searchType = 'DateRange';
                    } elseif ($field->getFieldtype() == 'date') {
                        $searchType = 'DatePicker';
                    }
    
                    //lấy điều kiện để search
                    if (in_array($field->getFieldtype(), self::noOrder)) {
                        $noOrder[] = $key;
                    }
    
                    $url = [
                        'path' => "object_listing_edit",
                    ];
    
                    $component = "ModalEdit";
                    $handleEdit = true;
    
                    if (in_array($field->getFieldtype(), self::notHandleEdit)) {
                        $handleEdit = false;
                    }
    
                    if (in_array($field->getFieldtype(), self::noSearch)) {
                        $searchType = 'nosearch';
                    }
    
                    $dataField[] = [
                        "key" => $key,
                        "type" => $field->getFieldtype(),
                        "title" => $field->getTitle(),
                        "tooltip" => $field->getTooltip(),
                        "searchType" => $searchType,
                        "options" => $options,
                        "handleEdit" => $handleEdit,
                        "url" => $url,
                        "component" => $component,
                    ];
    
                    $listFields[] = [
                        "type" => $field->getFieldtype(),
                        "name" => $key
                    ];
                }
            }

            $search = $request->get('search');
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
                            if (count($relation) && in_array($field, $relation)) {
                                $count = count($keyword);
                                $value = $keyword[$count - 1];
                                $listing->addConditionParam("`" . $field . "__id` = '" . $value . "'");
                                continue;
                            }
    
                            if (count($relations) && in_array($field, $relations)) {
                                $count = count($keyword);
                                $value = $keyword[$count - 1];
                                $listing->addConditionParam("`" . $field . "` like '%" . $value . "%'");
                                continue;
                            }
    
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
            $listing->setLocale($request->get('locale', \Pimcore\Tool::getDefaultLanguage()));
            $listing->setUnpublished(true);

            if ($limit == -1) {
                $limit = 10000;
            }

            dd($listing);
            

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * @Route("/detail", name="api_object_detail", methods={"GET"}, options={"expose"=true})
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
            $messageError = $this->validator->validate($condition, $request);
            if($messageError) return $this->sendError($messageError);

            $object = DataObject::getById($request->get('id'));
            $data = [];
            if ($object) {
                $lang = $request->get('_locale') ?? \Pimcore\Tool::getDefaultLanguage();
                $data['data'] = FieldController::getData($object, $lang);
                $data['data']['id'] = $request->get('id');

                return $this->sendResponse($data);
            }

            return $this->sendError('Object not found', 500);

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

}