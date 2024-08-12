<?php

namespace CorepulseBundle\Controller\Api;

use CorepulseBundle\Controller\Cms\FieldController;
use CorepulseBundle\Services\AssetServices;
use CorepulseBundle\Services\ClassServices;
use CorepulseBundle\Services\DataObjectServices;
use CorepulseBundle\Services\Helper\BlockJson;
use Pimcore\Db;

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
     * @Route("/detail", name="api_object_detail", methods={"GET"}, options={"expose"=true})
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
                        $classDefinition = ClassDefinition::getById($class['id']);

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
