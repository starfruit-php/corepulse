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
    const LIST_TYPE = [
        "input", "textarea", "wysiwyg", "password",
        "number", "numericRange", "slider", "numeric",
        "date", "datetime", "dateRange", "time", "manyToOneRelation",
        "select", 'multiselect', 'image', 'manyToManyRelation',
        'manyToManyObjectRelation', 'imageGallery', ''
    ];

    CONST TYPE_RESPONSIVE = [
        "fieldcollections" => null,
        "block" => null,
        "datetime" => 'string',
        "dateRange" => 'string',
        "checkbox" => 'boolean',
        "input" => 'string',
        "urlSlug" => 'string',
        "numeric" => 'number',
        "gender" => 'select',
        "manyToOneRelation" => 'select',
        "manyToManyObjectRelation" => 'multiselect',
        "manyToManyRelation" => 'multiselect',
        "advancedManyToManyRelation" => 'multiselect',
        "advancedManyToManyObjectRelation" => 'multiselect',
    ];

    CONST SYSTEM_FIELD = ['id' => 'number', 'key' => 'string', 'path' => 'string', 'published' => 'boolean', 'modificationDate' => 'date', 'creationDate' => 'date' ];

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
        $type = $fieldDefinition->getFieldType();

        if (isset(self::TYPE_RESPONSIVE[$type])) {
            $type = self::TYPE_RESPONSIVE[$type];
        }

        $data = [
            'name' => $fieldDefinition->getName(),
            'title' => $fieldDefinition->getTitle(),
            'invisible' => $fieldDefinition->getInvisible(),
            'visibleSearch' => $fieldDefinition->getVisibleSearch(),
            'visibleGridView' => $fieldDefinition->getVisibleGridView(),
            'fieldtype' => $fieldDefinition->getFieldType(),
            'localized' => $localized,
            'type' => $type,
        ];

        if (method_exists($fieldDefinition, 'getDisplayMode')) {
            $data['displayMode'] = $fieldDefinition->getDisplayMode();
        }

        if (method_exists($fieldDefinition, 'getChildren')) {
            $data['children'] = $fieldDefinition->getChildren();
        }

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

    public static function systemField($propertyVisibility = null)
    {
        $fields = [];
        $properties = self::SYSTEM_FIELD;

        foreach ($properties as $key => $property) {
            $fields[$key] = [
                "name" => $key,
                "title" => $key,
                "fieldtype" => "system",
                "type" => $property,
            ];

            if ($propertyVisibility) {
                $fields[$key]["visibleSearch"] = $propertyVisibility['search'][$key];
                $fields[$key]["visibleGridView"] = $propertyVisibility['grid'][$key];
            }
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
}
