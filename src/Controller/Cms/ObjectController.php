<?php

namespace CorepulseBundle\Controller\Cms;

use DateTime;
use DateTimeZone;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;
use CorepulseBundle\Services\ObjectServices;
use CorepulseBundle\Services\ReportServices;
use CorepulseBundle\Controller\Cms\FieldController;
use CorepulseBundle\Services\AssetServices;
use Pimcore\Model\User as AdminUser;
use Pimcore\Model\Asset;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pimcore\Model\DataObject\Service as DataObjectService;
use CorepulseBundle\Services\Helper\BlockJson;

class ObjectController extends FieldController
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
     *
     * @Route("/object/{object}", name="vuetify_object", options={"expose"=true}))
     */
    public function objectAction(Request $request, PaginatorInterface $paginator)
    {
        $object = $request->get("object");

        $classDefinition = ClassDefinition::getById($object);

        if (class_exists('\\Pimcore\\Model\\DataObject\\' . ucfirst($classDefinition->getName()))) {
            // Lớp tồn tại
            // Thực hiện các thao tác liên quan đến lớp này ở đây
            $listing = call_user_func_array('\\Pimcore\\Model\\DataObject\\' . $classDefinition->getName() . '::getList', [["unpublished" => true]]);
        } else {
            // Lớp không tồn tại
            // Xử lý khi lớp không tồn tại ở đây
            return $this->renderWithInertia('Pages/Object/Listing');
        }
        // call_user_func_array('\\Pimcore\\Model\\DataObject\\'. $constraint->class .'::getBy'. ucfirst($constraint->field), [$value, ['limit' => 1,'unpublished' => true]]);
        $dataJson = [
            "id" => $classDefinition->getId(),
            "name" => $classDefinition->getName(),
            "title" => $classDefinition->getTitle() ? $classDefinition->getTitle() :  $classDefinition->getName()
        ];
        $dataField = [];
        $fields = $classDefinition->getFieldDefinitions();
        $listFields = [];
        $chips = [];
        $relation = [];
        $relations = [];
        $chipsColor = [];
        $multiSelect = [];
        $colors = [];
        $search = [];
        $locale = $request->get('locale', $this->getLanguage());
        $noOrder = [];
        $viewData = ['metaTitle' => $classDefinition->getName()];

        $totalItems = 0;

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
        if ($request->isMethod("POST")) {
            $limit = $request->get("limit", 10);
            $page = $request->get("page", 1);
            $order = $request->get('order', 'desc');
            $orderKey = $request->get('orderKey', 'modificationDate');

            $listing->setLocale($locale);
            $listing->setUnpublished(true);
            if (!in_array($orderKey, $noOrder)) {
                $listing->setOrderKey($orderKey);
                $listing->setOrder($order);
            }
            $search = $request->get("search");
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

            if ($limit == -1) {
                $limit = 10000;
            }

            $listing = $paginator->paginate(
                $listing,
                $page,
                $limit
            );

            $totalItems = $listing->getPaginationData()["totalCount"];

            $listData = [];
            foreach ($listing as $item) {

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

                if (count($search) && isset($search['published'])) {
                    if ($search['published'] == 'Published' && $status == 'Published') {
                        $listData[] = $data;
                    }

                    if ($search['published'] == 'Draft' && $status == 'Draft') {
                        $listData[] = $data;
                    }

                    if ($search['published'] == 'Unpublished' && $status == 'Unpublished') {
                        $listData[] = $data;
                    }

                    continue;
                }

                $listData[] = $data;
            }

            // if ($orderKey == 'publish') {
            //     $listData = ReportServices::sortArrayByField($listData, $orderKey, $order);
            // }
            // dd($listData,  $dataField);
            $result = [
                "listing" => $listData,
                "fields" => $dataField,
                "classObject" => $dataJson,
                "totalItems" => $totalItems,
                "noOrder" => $noOrder,
                "chips" => $chips,
                "chipsColor" => $chipsColor,
                "multiSelect" => $multiSelect,
            ];

            return $this->renderWithInertia('Pages/Object/Listing', $result, $viewData);
        }

        return $this->renderWithInertia('Pages/Object/Listing', ["classObject" =>  $dataJson, "fields" => $dataField,], $viewData);
    }

    /**
     * @Route("/object/detail/{id}", name="vuetify_object_detail", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function objectDetailAction(Request $request)
    {
        $data = [];
        $breadcrumb = [];
        $sidebar = [];
        $dataJson = [];
        $languageOption = \Pimcore\Tool::getValidLanguages();
        $language = $request->get('locale', $this->getLanguage());

        $object = DataObject::getById($request->get('id'));

        if ($request->getMethod() == Request::METHOD_POST) {
            $params = $request->request->all();
            $params['children'] = json_decode($params['children'], true);

            $objectKey = $params['objectKey'];

            try {
                if ($objectKey !== $object->getKey()) {
                    $object->setKey(\Pimcore\Model\Element\Service::getValidKey($objectKey, 'object'));
                }

                $object = ObjectServices::edit($params, $object);
            } catch (\Throwable $th) {
                return new JsonResponse(['warning' => $th->getMessage()]);
            }

            return new JsonResponse(['status' => true]);
        }

        if ($object) {
            $lang = $request->get('language') ?? $this->getLanguage();
            $data = $this->getData($object, $lang);
            $data['id'] = $request->get('id');
            $classes = $object->getClass();

            $draft = $this->checkLastest($object);

            if ($draft) {
                $status = 'Draft';
            } else {
                if ($object->getPublished()) {
                    $status = 'Published';
                } else {
                    $status = 'Unpublished';
                }
            }

            $breadcrumb = [
                'root' => [
                    'name' => $classes->getName(),
                    'title' => $classes->getTitle() ? $classes->getTitle() : $classes->getName(),
                    'id' => $classes->getId(),
                ],
                'item' => $object->getKey(),
                'status' => $status,
            ];

            $dataJson = [
                'id' => $classes->getId(),
                "name" => $classes->getName(),
                'title' => $classes->getTitle() ? $classes->getTitle() : $classes->getName(),
            ];

            $sidebar = self::getJson($object);

            $sidebar['language'] = $lang;

        }

        $urlSlug = method_exists($object, 'getSlug') ? $object->getSlug() ? $object->getSlug()[0]->getSlug() : '' : '';
        $viewData = ['metaTitle' => $classes->getName() . ': ' . $object->getKey()];
        // dd($viewData);

        return $this->renderWithInertia('Pages/Object/Detail', [
            'data' => $data,
            'breadcrumb' => $breadcrumb,
            'language' => $language,
            'languageOption' => $languageOption,
            'sidebar' => $sidebar,
            "classObject" => $dataJson,
            'urlSlug' => $urlSlug,
        ], $viewData);
    }

    /**
     * @Route("/object/{object}/create", name="object_create", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function objectCreate(Request $request)
    {
        $sidebar = [];

        $languageOption = \Pimcore\Tool::getValidLanguages();
        $language = $request->get('locale', $this->getLanguage());
        $classId = $request->get("object");
        $objectKey = $request->get('objectKey');

        $string = '\\Pimcore\\Model\\DataObject\\' . ucfirst($classId);
        $object = new $string;

        if ($request->getMethod() == Request::METHOD_POST) {

            $params = $request->request->all();
            $params['children'] = json_decode($params['children'], true);

            try {
                $object = ObjectServices::create($params, $object);
            } catch (\Throwable $th) {
                return new JsonResponse(['warning' => $th->getMessage()]);
            }

            return new JsonResponse(['status' => true]);
        }
        $breadcrumb = [];
        $data = [];
        $dataJson = [];

        if ($object) {
            $lang = $request->get('language') ?? $this->getLanguage();
            $data = $this->getData($object, $lang);
            $classes = $object->getClass();

            $breadcrumb = [
                'root' => [
                    'name' => $classes->getName(),
                    'title' => $classes->getTitle() ? $classes->getTitle() : $classes->getName(),
                    'id' => $classes->getId(),
                ],
                'item' => $object->getKey(),
                'status' => $object->getPublished() ? 'Published' : 'Unpublished',
            ];

            $dataJson = [
                'id' => $classes->getId(),
                "name" => $classes->getName(),
                'title' => $classes->getTitle() ? $classes->getTitle() : $classes->getName(),
            ];

            $sidebar['language'] = $language;
        }
        $data['objectKey'] = $objectKey;

        return $this->renderWithInertia('Pages/Object/Create', [
            'data' => $data,
            'breadcrumb' => $breadcrumb,
            'language' => $language,
            'languageOption' => $languageOption,
            "classObject" => $dataJson,
            'sidebar' => $sidebar
        ]);
    }

    /**
     * @Route("/object/{object}/delete", name="object_delete", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function objectDelete(Request $request)
    {
        if (is_array($request->get('id'))) {
            // try {
            foreach ($request->get('id') as $id) {

                $object = DataObject::getById($id);

                $object->delete();
            }
            // } catch (\Throwable $th) {
            //     return new JsonResponse(['warning' => $th->getMessage()]);
            // }
        } else {
            $object = DataObject::getById($request->get('id'));
            $object->delete();
            return $this->redirectToRoute('vuetify_object', ['object' => $request->get('object')]);
        }

        return new JsonResponse();
    }

    static public function getJson($object)
    {
        $userOwner =  AdminUser::getById($object->getUserOwner());
        $userModification = AdminUser::getById($object->getUserModification());

        $data = [
            'id' => $object->getId(),
            'key' => $object->getKey(),
            'published' => $object->getPublished(),
            'fullPath' => $object->getFullPath(),
            'creationDate' => $object->getCreationDate() ? date('d-m-Y', $object->getCreationDate()) : '',
            'modificationDate' => $object->getModificationDate() ? date('d-m-Y', $object->getModificationDate()) : '',
            'className' => $object->getClassName(),
            'classId' => $object->getClassId(),
            'classTitle' => $object->getClass() ? $object->getClass()->getTitle() : '',
            'userOwner' => $userOwner ? $userOwner->getName() : '',
            'userModification' => $userModification ? $userModification->getName() : '',
        ];

        return $data;
    }

    /**
     * @Route("/object-field-collection-layout", name="object_field_collection_layout", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function objectFieldCollectionLayoutAction(Request $request)
    {
        $data = $request->get('data');
        $response = [];

        if ($data && count($data)) {
            foreach ($data as $type) {
                $textListing = "Pimcore\\Model\\DataObject\\Fieldcollection\\Data\\" . ucfirst($type);
                $listing = new $textListing();
                $itemField = [];
                foreach ($listing->getDefinition()->getLayoutDefinitions()->getChildren() as $k => $item) {
                    // check lại truyền lang cho đúng với cái chọn
                    $itemField[$type] = FieldController::extractStructure($item, $listing, true, $this->getLanguage());
                }
                $response[$type] = $itemField;
            }
        }

        return $this->json($response);
    }

    /**
     * @Route("/object-selected-edit", name="object_selected_edit", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function objectSelectedEditAction(Request $request)
    {
        $publish = $request->get('published');

        try {
            if (is_array($request->get('id'))) {
                foreach ($request->get('id') as $id) {
                    $object = DataObject::getById($id);
                    if ($publish && $publish == 'Published') {
                        $object->setPublished(true);
                        $object->save();
                    }

                    if ($publish && $publish == 'Unpublished') {
                        $object->setPublished(false);
                        $object->save();
                    }
                }
            } else {
                $object = DataObject::getById($request->get('id'));

                if ($publish && $publish == 'Published') {
                    $object->setPublished(true);
                    $object->save();
                }

                if ($publish && $publish == 'Unpublished') {
                    $object->setPublished(false);
                    $object->save();
                }
            }
        } catch (\Throwable $th) {
            return new JsonResponse(['warning' => $th->getMessage()]);
        }

        return new JsonResponse();
    }

    /**
     * @Route("/object-listing-edit", name="object_listing_edit", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function objectListingEditAction(Request $request)
    {
        $params = $request->request->all();
        $object = DataObject::getById($params['id']);

        $fieldType = $params['type'];
        $fieldValue = $params['value'];
        $lang = $this->getLanguage();

        if ($fieldType == 'imageGallery') {
            foreach ($fieldValue as $key => $value) {
                $fieldValue[$key] = json_decode($value, true);
            }
            $params['value'] = $fieldValue;
        }

        try {
            switch ($fieldType) {
                case 'key':
                    $object->setKey(\Pimcore\Model\Element\Service::getValidKey($fieldValue, 'object'));
                    break;
                case 'published':
                    $fieldValue == 'Published' ? $object->setPublished(true) : $object->setPublished(false);
                    break;
                default:
                    ObjectServices::checkType($object, $params, $lang);
                    break;
            }

            $object->save();
        } catch (\Throwable $th) {
            return new JsonResponse(['warning' => $th->getMessage()]);
        }

        return new JsonResponse();
    }

    /**
     * @Route("/object-check-key", name="object_check_key", methods={"GET","POST"}, options={"expose"=true}))
     */
    public function checkKey(Request $request)
    {
        $classId = $request->get("object");
        $objectKey = $request->get('objectKey');

        $string = '\\Pimcore\\Model\\DataObject\\' . ucfirst(str_replace(' ', '', $classId));
        $object = new $string;

        try {
            $object->setKey(\Pimcore\Model\Element\Service::getValidKey($objectKey, 'object'));

            DataObjectService::createFolderByPath("/" . $classId);
            $object->setParent(\Pimcore\Model\DataObject::getByPath("/" . $classId));

            $object->save();

            return new JsonResponse(['id' => $object->getId()]);
        } catch (\Throwable $th) {

            return new JsonResponse(['warning' => $th->getMessage()]);
        }
    }
}
