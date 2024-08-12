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

    public static function isValid($classId)
    {
        $objectSetting = Db::get()->fetchAssociative('SELECT * FROM `vuetify_settings` WHERE `type` = "object"', []);
        if ($objectSetting !== null && $objectSetting) {
            $data = json_decode($objectSetting['config']) ?? [];

            if (!empty($data) && in_array($classId, $data)) {
                return true;
            }
        }

        return false;
    }

    public static function examplesAction($classId)
    {
        $data = [];
        try {
            $classDefinition = ClassDefinition::getById($classId);
            $propertyVisibility = $classDefinition->getPropertyVisibility();
            $fieldDefinitions = $classDefinition->getFieldDefinitions();
            $result = [];
            foreach ($fieldDefinitions as $key => $fieldDefinition) {
                if ($fieldDefinition instanceof ClassDefinition\Data\Localizedfields) {
                    foreach ($fieldDefinition->getChildren() as $child) {
                        $result[$child->name] = self::getFieldProperty($child, true);
                    }
                } else {
                    $result[$key] = self::getFieldProperty($fieldDefinition);
                }
            }

            $data = [ 'fields' => $result, 'class' => $classDefinition->getName() ];

            $data = array_merge($data, $propertyVisibility);
        } catch (\Throwable $th) {
        }

        return $data;
    }

    public static function getFieldProperty($fieldDefinition, $localized = false)
    {
        $data = get_object_vars($fieldDefinition);
        $data['fieldtype'] = $fieldDefinition->getFieldType();
        $data['localized'] = $localized;

        return $data;
    }

    public static function updateTable($className, $visibleFields, $tableView = false)
    {
        $config = self::getConfig($className);

        if ($config) {
            $saveData = json_decode($config['visibleFields'], true);
            if ($tableView) {
                $saveData['tableView'] = $visibleFields;
            } else if (!isset($visibleFields['tableView'])) {
                $visibleFields['tableView'] = isset($saveData['tableView']) ? $saveData['tableView'] : [];
                $saveData = $visibleFields;
            }

            Db::get()->update(
                'corepulse_class',
                [
                    'visibleFields' => json_encode($saveData),
                ],
                [
                    'className' => $className,
                ]
            );

            return true;
        }

        return false;
    }

    public static function systemField($propertyVisibility)
    {
        $fields = [];
        $properties = ['id', 'key', 'path', 'published', 'modificationDate', 'creationDate'];

        foreach ($properties as $property) {
            $fields[$property] = [
                "name" => $property,
                "title" => $property,
                "visibleSearch" => $propertyVisibility['search'][$property],
                "visibleGridView" => $propertyVisibility['grid'][$property],
                "fieldtype" => "system",
                "type" => $property,
            ];
        }

        return $fields;
    }

    public static function getConfig($className)
    {
        $item = Db::get()->fetchAssociative('SELECT * FROM `corepulse_class` WHERE `className` = "' . $className . '"', []);
        if (!$item) {
            Db::get()->insert('corepulse_class', [
                'className' => $className,
            ]);
            $item = Db::get()->fetchAssociative('SELECT * FROM `corepulse_class` WHERE `className` = "' . $className . '"', []);
        }

        return $item;
    }

    public static function getVisibleGridView($fields)
    {
        return array_filter($fields, function($value) {
            return $value['visibleGridView'] === true;
        });
    }

    public static function getVisibleSearch($fields)
    {
        return array_filter($fields, function($value) {
            return $value['visibleSearch'] === true;
        });
    }

    public static function filterFill($fields, $tableView)
    {
        if(empty($tableView)) {
            return self::getVisibleGridView($fields);
        }

        $data = [];
        foreach ($tableView as $view) {
            $data[$view] = $fields[$view];
        }

        return $data;
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
