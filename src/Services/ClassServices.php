<?php

namespace CorepulseBundle\Services;

use CorepulseBundle\Controller\Cms\FieldController;
use CorepulseBundle\Services\Helper\BlockJson;
use Google\Service\AIPlatformNotebooks\Status;
use Pimcore\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Pimcore\Model\Asset;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pimcore\Model\DataObject\ClassDefinition;


class ClassServices
{
    const chipField = [
        "select", "multiselect", "manyToOneRelation", "manyToManyObjectRelation",
        "manyToManyRelation", "advancedManyToManyRelation", "advancedmanyToManyObjectRelation"
    ];
    const notHandleEdit = [
        "advancedManyToManyRelation",
        "advancedmanyToManyObjectRelation"
    ];

    public static function examplesAction($classId)
    {
        $classDefinition = ClassDefinition::getById($classId);
        $propertyVisibility = $classDefinition->getPropertyVisibility();
        $fieldDefinitions = $classDefinition->getFieldDefinitions();
        $result = [];
        foreach ($fieldDefinitions as $key => $fieldDefinition) {
            if ($fieldDefinition instanceof ClassDefinition\Data\Localizedfields) {
                foreach ($fieldDefinition->getChildren() as $child) {
                    $result[$child->name] = self::getFieldProperty($child);
                }
            } else {
                $result[$key] = self::getFieldProperty($fieldDefinition);
            }
        }

        return $result;
    }

    private static function getFieldProperty($fieldDefinition)
    {
        $url = [
            'path' => "object_listing_edit",
        ];
        $component = "ModalEdit";
        $handleEdit = true;
        $options = [];

        if (in_array($fieldDefinition->getFieldtype(), self::chipField)) {
            $options = FieldController::getOptions($fieldDefinition->getFieldtype(), $fieldDefinition);
        }

        if (in_array($fieldDefinition->getFieldtype(), self::notHandleEdit)) {
            $handleEdit = false;
        }

        return [
            "visible" => $fieldDefinition->visibleGridView,
            "key" => $fieldDefinition->name,
            "type" => $fieldDefinition->getFieldtype(),
            "title" => $fieldDefinition->getTitle(),
            "tooltip" => $fieldDefinition->getTooltip(),
            "searchType" => FieldServices::getType($fieldDefinition->getFieldtype()),
            "options" => $options,
            "handleEdit" => $handleEdit,
            "url" => $url,
            "component" => $component,
        ];
    }

    public static function updateTable($className, $visibleFiels)
    {
        try {
            $object = self::getData($className);
            // dd(json_encode($visibleFiels));
            if ($object) {
                Db::get()->update(
                    'corepulse_class',
                    [
                        'visibleFiels' => json_encode($visibleFiels),
                    ],
                    [
                        'className' => $className,
                    ]
                );

                $response = "Update successfully";
            } else {
                $response = "Object not found";
            }

            return $response;

        } catch (\Throwable $th) {
            return new JsonResponse(['warning' => $th->getMessage()]);
        }

    }

    public static function getData($className)
    {
        $item = Db::get()->fetchAssociative('SELECT * FROM `corepulse_class` WHERE `className` = "' . $className . '"', []);
        if (!$item) {
            Db::get()->insert('corepulse_class', [
                'className' => $className,
            ]);
            $item = Db::get()->fetchAssociative('SELECT * FROM `corepulse_class` WHERE `className` = "' . $className . '"', []);
        }
        // if ($item['visibleFiels']) {
        //     $item['visibleFiels'] = json_decode($item['visibleFiels'], true);
        // } else {
        //     $item['visibleFiels'] = [];
        // }

        return $item;
    }

    public static function getDataField($item, $field, $colors) {
        if ($field->type == "date") {
            $data = $item->{"get" . ucfirst($field->key)}()?->format("Y/m/d");
        }

        if ($field->type == "datetime") {
            $data = $item->{"get" . ucfirst($field->key)}()?->format("Y/m/d H:i");
        }

        if ($field->type == "dateRange") {
            $value = $item->{"get" . ucfirst($field->key)}();
            $data = $value?->getStartDate()?->format("Y/m/d") . " - " . $value?->getEndDate()?->format("Y/m/d");
        }

        if ($field->type == "select") {
            if ($item) {
                $value = $item->{"get" . ucfirst($field->key)}();
                if ($value) {
                    $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                    while (in_array($color, $colors)) {
                        $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                    }
                    $colors[] = $color;

                    $chipsColor[$value] = $color;
                    $data = $value;
                } else {
                    $data = $value;
                }
            }
        }

        if ($field->type == "multiselect") {
            if ($item) {
                $value = $item->{"get" . ucfirst($field->key)}();

                if ($value && is_array($value) && count($value)) {
                    foreach ($value as $k => $v) {
                        $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                        while (in_array($color, $colors)) {
                            $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                        }
                        $colors[] = $color;

                        $chipsColor[$v] = $color;
                        $data[] = $v;
                    }
                } else {
                    $data = '';
                }
            }
        }

        if ($field->type == "manyToOneRelation") {
            if ($item) {
                $value = $item->{"get" . ucfirst($field->key)}();
                if ($value) {
                    $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                    while (in_array($color, $colors)) {
                        $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                    }
                    $colors[] = $color;

                    $chipsColor[$value->getKey()] = $color;
                    $data = [
                        'key' => $value->getKey(),
                        'data' => BlockJson::getValueRelation($value, 3)
                    ];
                } else {
                    $data = $value;
                }
            }
        }

        if ($field->type == "manyToManyObjectRelation") {
            if ($item) {
                $value = $item->{"get" . ucfirst($field->key)}();

                if ($value && is_array($value) && count($value)) {
                    foreach ($value as $k => $v) {
                        $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));

                        // Kiểm tra xem màu đã được sử dụng chưa, nếu đã sử dụng, tạo lại
                        while (in_array($color, $colors)) {
                            $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                        }

                        // Thêm màu vào mảng
                        $colors[] = $color;

                        $chipsColor[$v->getKey()] = $color;
                        $data[] = [
                            'key' => $v->getKey(),
                            'data' => BlockJson::getValueRelation($v, 3)
                        ];
                    }
                } else {
                    $data = '';
                }
            }
        }

        if ($field->type == "manyToManyRelation") {
            if ($item) {
                $value = $item->{"get" . ucfirst($field->key)}();

                if ($value && is_array($value) && count($value)) {
                    foreach ($value as $k => $v) {
                        $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));

                        // Kiểm tra xem màu đã được sử dụng chưa, nếu đã sử dụng, tạo lại
                        while (in_array($color, $colors)) {
                            $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                        }

                        // Thêm màu vào mảng
                        $colors[] = $color;

                        $chipsColor[$v->getKey()] = $color;
                        $data[] = [
                            'key' => $v->getKey(),
                            'data' => BlockJson::getValueRelation($v, 3)
                        ];
                    }
                } else {
                    $data = '';
                }
            }
        }

        if ($field->type == "image") {
            if ($item) {
                $value = $item->{"get" . ucfirst($field->key)}();

                if ($value && $value instanceof Asset\Image) {

                    $publicURL = AssetServices::getThumbnailPath($value);

                    $data = "<div class='tableCell--titleThumbnail preview--image d-flex align-center'>
                    <img class='me-2' src=' " .  $publicURL . "'></div>";
                } else {
                    $data = $value;
                }
            }
        }

        if ($field->type == "imageGallery") {
            if ($item) {
                $value = $item->{"get" . ucfirst($field->key)}();

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
                    $data = $va;
                } else {
                    $data = null;
                }
            }
        }

        if ($field->type == "urlSlug") {
            if ($item) {
                $value = $item->{"get" . ucfirst($field->key)}();

                if ($value) {
                    $data = $value[0]->getSlug();
                } else {
                    $data = '';
                }
            }
        }

        $data = $item->{"get" . ucfirst($field->key)}();

        return $data;
    }
}