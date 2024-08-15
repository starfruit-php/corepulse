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
    const SYSTEM_FIELD = ['id', 'key', 'path', 'published', 'modificationDate', 'creationDate'];

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

    public static function systemField($propertyVisibility = null)
    {
        $fields = [];
        $properties = self::SYSTEM_FIELD;

        foreach ($properties as $property) {
            $fields[$property] = [
                "name" => $property,
                "title" => $property,
                "fieldtype" => "system",
                "type" => $property,
            ];

            if ($propertyVisibility) {
                $fields[$property]["visibleSearch"] = $propertyVisibility['search'][$property];
                $fields[$property]["visibleGridView"] = $propertyVisibility['grid'][$property];
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
