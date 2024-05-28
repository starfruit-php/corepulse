<?php

namespace CorepulseBundle\Controller\Api;

use CorepulseBundle\Controller\Cms\FieldController;
use CorepulseBundle\Services\AssetServices;
use CorepulseBundle\Services\ClassServices;
use CorepulseBundle\Services\DocumentServices;
use CorepulseBundle\Services\Helper\BlockJson;
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

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\Output;
use Doctrine\DBAL\Schema\Schema;

use DateTime;

/**
 * @Route("/object")
 */
class ObjectController extends BaseController
{
    protected ?Schema $schema = null;
    protected BufferedOutput $output;
    
    protected function getSchema($db): Schema
    {
        return $this->schema ??= $db->createSchemaManager()->introspectSchema();
    }

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
     * @Route("/create-table", name="api_object_create_table", methods={"GET"}, options={"expose"=true})
     *
     * {mô tả api}
     *
     * @param Cache $cache
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function createTable(
        Request $request,
        PaginatorInterface $paginator,
        Connection $db)
    {
        $this->output = new BufferedOutput(Output::VERBOSITY_NORMAL, true);
        $response = '';
        $tablesToInstall = ['corepulse_class' => 'CREATE TABLE `corepulse_class` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `className` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `visibleFiels` LONGTEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'];
        foreach ($tablesToInstall as $name => $statement) {
            if ($this->getSchema($db)->hasTable($name)) {
                $this->output->write(sprintf(
                    '     <comment>WARNING:</comment> Skipping table "%s" as it already exists',
                    $name
                ));

                $response = "Table " . $name. " already exists";
                continue;
            }

            $db->executeQuery($statement);
            $response = "Tables " . $name. " created successfully";
        }

        return $this->sendResponse($response);
    }
    /**
     * @Route("/test", name="api_object_test", methods={"GET"}, options={"expose"=true})
     *
     * {mô tả api}
     *
     * @param Cache $cache
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function test(
        Request $request,
        PaginatorInterface $paginator)
    {
        try {
            $object = $request->get("object");
            $classDefinition = ClassDefinition::getById($object);
            if ($classDefinition) {
                $className = $classDefinition->getName();
                $propertyVisibility = $classDefinition->getPropertyVisibility();
        
                $propertys = [];
                if ($propertyVisibility) {
                   $propertys = $propertyVisibility['grid'];
                }
                $fields = ClassServices::examplesAction($object);
                $result = array_merge($propertys, $fields);
                // dd($fields);
                $update = ClassServices::updateTable($className, $fields);

                return $this->sendResponse($update);

            }
            return $this->sendError("Object not found", 500);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
    /**
     * @Route("/listing", name="api_object_list", methods={"GET"}, options={"expose"=true})
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
                $listing->setLocale($request->get('_locale', \Pimcore\Tool::getDefaultLanguage()));
                $listing->setUnpublished(true);
    
                $paginationData = $this->helperPaginator($paginator, $listing, $page, $limit);
                $data = array_merge(
                    [
                        'data' => []
                    ],
                    $paginationData,
                );

                $dataJson = [
                    "id" => $classDefinition->getId(),
                    "name" => $classDefinition->getName(),
                    "title" => $classDefinition->getTitle() ? $classDefinition->getTitle() :  $classDefinition->getName()
                ];

                $data['data']['totalItems'] =  $listing->count();
                $data['data']['classObject'] =  $dataJson;
    
                $fields = ClassServices::getData($classDefinition->getName());
                $visibleFiels = [];
                if ($fields) {
                    $visibleFiels = json_decode($fields['visibleFiels']);
                    $data['data']['fields'] = $visibleFiels;
                }

                // dd($fields['visibleFiels']);
                $colors = [];
                $chips = [];
                $chipsColor = [];
                $multiSelect = [];
                $noOrder = [];

                foreach($listing as $item)
                {
                    $draft = $this->checkLastest($item);
    
                    if ($draft) {
                        $status = 'Draft';
                        $chipsColor['published']['Draft'] = 'green';
                    } else {
                        if ($item->getPublished()) {
                            $status = 'Published';
                            $chipsColor['published']['Published'] = 'primary';
                        } else {
                            $status = 'Unpublished';
                            $chipsColor['published']['Unpublished'] = 'red';
                        }
                    }
    
                    $json = [
                        'id' => $item->getId(),
                        "key" => $item->getKey(),
                        "modificationDate" => $this->getTimeAgo($item->getModificationDate()),
                        "published" => $status,
                        "draft" => $draft,
                        "unSelecte" => false
                    ];
    
                    foreach ($visibleFiels as $key => $value) {
                        $function = "get" . ucwords($key);
                        $json[$value->key] = ClassServices::getDataField($item, $value, $colors);
                    }
                    $data['data']['listing'][] = $json;
                }
                
                $data['data'][] = [
                    "noOrder" => $noOrder,
                    "chips" => $chips,
                    "chipsColor" => $chipsColor,
                    "multiSelect" => $multiSelect,
                ];

                return $this->sendResponse($data);
            } 

            return $this->sendError("Object not found", 500);

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
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
            $listFields = [];
            $colors = [];
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

            $paginationData = $this->helperPaginator($paginator, $listing, $page, $limit);
            $data = array_merge(
                [
                    'data' => []
                ],
                $paginationData,
            );

            // dd($listing);
            foreach($listing as $item)
            {
                $json = self::listingResponse($item, $listFields, $colors);
                $data['data'][] = $json;
            }

            return $this->sendResponse($data);

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

    public function listingResponse($item, $listFields, $colors) {
        $draft = $this->checkLastest($item);

        if ($draft) {
            $status = 'Draft';
            $chipsColor['published']['Draft'] = 'green';
        } else {
            if ($item->getPublished()) {
                $status = 'Published';
                $chipsColor['published']['Published'] = 'primary';
            } else {
                $status = 'Unpublished';
                $chipsColor['published']['Unpublished'] = 'red';
            }
        }
        $data = [
            "key" => $item->getKey(),
            "modificationDate" => $this->getTimeAgo($item->getModificationDate()),
            "published" => $status,
            "draft" => $draft,
            "id" => $item->getId(),
            "unSelecte" => false
        ];

        foreach ($listFields as $field) {
            if ($field["type"] == "date") {
                $data[$field["name"]] = $item->{"get" . ucfirst($field["name"])}()?->format("Y/m/d");
                continue;
            }

            if ($field["type"] == "datetime") {
                $data[$field["name"]] = $item->{"get" . ucfirst($field["name"])}()?->format("Y/m/d H:i");
                continue;
            }

            if ($field["type"] == "dateRange") {
                $value = $item->{"get" . ucfirst($field["name"])}();
                $data[$field["name"]] = $value?->getStartDate()?->format("Y/m/d") . " - " . $value?->getEndDate()?->format("Y/m/d");
                continue;
            }

            if ($field["type"] == "select") {
                if ($item) {
                    $value = $item->{"get" . ucfirst($field["name"])}();
                    if ($value) {
                        $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                        while (in_array($color, $colors)) {
                            $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                        }
                        $colors[] = $color;

                        $chipsColor[$field["name"]][$value] = $color;
                        $data[$field["name"]] = $value;
                    } else {
                        $data[$field["name"]] = $value;
                    }
                }
                continue;
            }

            if ($field["type"] == "multiselect") {
                if ($item) {
                    $value = $item->{"get" . ucfirst($field["name"])}();

                    if ($value && is_array($value) && count($value)) {
                        foreach ($value as $k => $v) {
                            $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                            while (in_array($color, $colors)) {
                                $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                            }
                            $colors[] = $color;

                            $chipsColor[$field["name"]][$v] = $color;
                            $data[$field["name"]][] = $v;
                        }
                    } else {
                        $data[$field["name"]] = '';
                    }
                }
                continue;
            }

            if ($field["type"] == "manyToOneRelation") {
                if ($item) {
                    $value = $item->{"get" . ucfirst($field["name"])}();
                    if ($value) {
                        $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                        while (in_array($color, $colors)) {
                            $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                        }
                        $colors[] = $color;

                        $chipsColor[$field["name"]][$value->getKey()] = $color;
                        $data[$field["name"]] = [
                            'key' => $value->getKey(),
                            'data' => BlockJson::getValueRelation($value, 3)
                        ];
                    } else {
                        $data[$field["name"]] = $value;
                    }
                }
                continue;
            }

            if ($field["type"] == "manyToManyObjectRelation") {
                if ($item) {
                    $value = $item->{"get" . ucfirst($field["name"])}();

                    if ($value && is_array($value) && count($value)) {
                        foreach ($value as $k => $v) {
                            $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));

                            // Kiểm tra xem màu đã được sử dụng chưa, nếu đã sử dụng, tạo lại
                            while (in_array($color, $colors)) {
                                $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                            }

                            // Thêm màu vào mảng
                            $colors[] = $color;

                            $chipsColor[$field["name"]][$v->getKey()] = $color;
                            $data[$field["name"]][] = [
                                'key' => $v->getKey(),
                                'data' => BlockJson::getValueRelation($v, 3)
                            ];
                        }
                    } else {
                        $data[$field["name"]] = '';
                    }
                }
                continue;
            }

            if ($field["type"] == "manyToManyRelation") {
                if ($item) {
                    $value = $item->{"get" . ucfirst($field["name"])}();

                    if ($value && is_array($value) && count($value)) {
                        foreach ($value as $k => $v) {
                            $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));

                            // Kiểm tra xem màu đã được sử dụng chưa, nếu đã sử dụng, tạo lại
                            while (in_array($color, $colors)) {
                                $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                            }

                            // Thêm màu vào mảng
                            $colors[] = $color;

                            $chipsColor[$field["name"]][$v->getKey()] = $color;
                            $data[$field["name"]][] = [
                                'key' => $v->getKey(),
                                'data' => BlockJson::getValueRelation($v, 3)
                            ];
                        }
                    } else {
                        $data[$field["name"]] = '';
                    }
                }
                continue;
            }

            if ($field["type"] == "image") {
                if ($item) {
                    $value = $item->{"get" . ucfirst($field["name"])}();

                    if ($value && $value instanceof Asset\Image) {

                        $publicURL = AssetServices::getThumbnailPath($value);

                        $data[$field["name"]] = "<div class='tableCell--titleThumbnail preview--image d-flex align-center'>
                        <img class='me-2' src=' " .  $publicURL . "'></div>";
                    } else {
                        $data[$field["name"]] = $value;
                    }
                }
                continue;
            }

            if ($field["type"] == "imageGallery") {
                if ($item) {
                    $value = $item->{"get" . ucfirst($field["name"])}();

                    if ($value && $value->getItems()) {
                        $gallery = $value->getItems();
                        $va = "<span class='tableCell--titleThumbnail preview--image d-flex align-center'>";
                        foreach ($gallery as $k => $v) {
                            $image = $v->getImage();
                            if ($image) {
                                $publicURL = AssetServices::getThumbnailPath($image);

                                $va .= "<img data-id='" . $image->getId() . "' data-path='" . $image->getFullPath() . "' class='me-2' src=' " .  $publicURL . "'>";
                            }
                        }
                        $va .= '</span>';
                        $data[$field["name"]] = $va;
                    } else {
                        $data[$field["name"]] = null;
                    }
                }
                continue;
            }

            if ($field["type"] == "urlSlug") {
                if ($item) {
                    $value = $item->{"get" . ucfirst($field["name"])}();

                    if ($value) {
                        $data[$field["name"]] = $value[0]->getSlug();
                    } else {
                        $data[$field["name"]] = '';
                    }
                }
                continue;
            }

            $data[$field["name"]] = $item->{"get" . ucfirst($field["name"])}();
        }

        return $data;
    }

}