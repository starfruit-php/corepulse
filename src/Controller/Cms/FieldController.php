<?php

namespace CorepulseBundle\Controller\Cms;

use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Symfony\Component\Routing\Annotation\Route;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Fieldcontainer;
use CorepulseBundle\Services\Helper\ObjectJson;
use CorepulseBundle\Services\Helper\JsonHelper;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Db;

/**
 * @Route("/field")
 */
class FieldController extends BaseController
{

    const KEY_DOCUMENT = 'Document';
    const KEY_OBJECT = 'DataObject';
    const KEY_ASSET = 'Asset';

    const TYPES = [
        "input", "textarea", "wysiwyg", "password",
        "number", "numericRange", "slider", "numeric",
        "date", "datetime", "dateRange", "time", "checkbox"

    ];

    const listField = [
        "input", "textarea", "wysiwyg", "password", "checkbox", "video",
        "number", "numericRange", "slider", "numeric", "select", "multiselect",
        "date", "datetime", "dateRange", "time", "manyToOneRelation", "link", "manyToManyObjectRelation",
        "image", "imageGallery", "fieldcollections", "geopoint", "urlSlug", "manyToManyRelation"
    ];

    static public function getData($object, $lang = 'vi')
    {
        $data = [];
        $class = $object->getClass();
        if ($class->getLayoutDefinitions()) {
            $data = self::extractStructure($class->getLayoutDefinitions(), $object, true, $lang);
        }

        return $data;
    }

    static public function extractStructure($layoutDefinition, $object, $getValue = true, $lang = null)
    {

        $type = null;
        $title = null;
        $options = [];

        if ($layoutDefinition instanceof Layout\Tabpanel) {
            $type = 'tabpanel';

            try {
                $title = $layoutDefinition->getTitle();
            } catch (\Throwable $th) {
                $title = null;
            }
        } elseif ($layoutDefinition instanceof Layout\Panel) {
            $type = 'panel';
            if ($layoutDefinition->getName() != 'pimcore_root') {

                try {
                    $title = $layoutDefinition->getTitle();
                } catch (\Throwable $th) {
                    $title = null;
                }
            }
        } elseif ($layoutDefinition instanceof Layout\Text) {
            $type = 'text';
            try {
                $title = $layoutDefinition->getTitle();
            } catch (\Throwable $th) {
                $title = null;
            }
        } elseif ($layoutDefinition instanceof Layout\Fieldset) {
            $type = 'fieldset';
            try {
                $title = $layoutDefinition->getTitle();
            } catch (\Throwable $th) {
                $title = null;
            }
        } elseif ($layoutDefinition instanceof Layout\Accordion) {
            $type = 'accordion';
            try {
                $title = $layoutDefinition->getTitle();
            } catch (\Throwable $th) {
                $title = null;
            }
        } elseif ($layoutDefinition instanceof Layout\Region) {
            $type = 'region';
            try {
                $title = $layoutDefinition->getTitle();
            } catch (\Throwable $th) {
                $title = null;
            }
        } else {
            if (method_exists($layoutDefinition, 'getFieldType')) {
                $type = $layoutDefinition->getFieldType();
                try {
                    $title = $layoutDefinition->getTitle();
                } catch (\Throwable $th) {
                    $title = null;
                }
            } else {
                $type = '';
            }
        }

        $structure = [
            'type'  => $type,
            'name'  => $layoutDefinition->getName(),
            'title' => $title,
        ];

        $attributes = ['invisible', 'noteditable', 'mandatory', 'index', 'locked', 'collapsible', 'collapsed'];

        foreach ($attributes as $attribute) {
            $method = 'get' . ucfirst($attribute);
            if (method_exists($layoutDefinition, $method)) {
                $structure[$attribute] = $layoutDefinition->$method();
            }
        }

        if ($layoutDefinition instanceof Layout\Text) {
            $structure['value'] = $layoutDefinition->getHtml();
        }

        if (in_array($structure['type'], self::listField) && $getValue) {
            $structure['value'] = JsonHelper::getValueByType($object, $layoutDefinition, $lang);
        }

        if ($structure['type'] == 'block') {
            $getValue = false;
            $structure['value'] = JsonHelper::getValueByType($object, $layoutDefinition, $lang);
        }

        if (method_exists($layoutDefinition, 'getChildren')) {
            $structure['children'] = [];
            foreach ($layoutDefinition->getChildren() as $child) {
                array_push($structure['children'], self::extractStructure($child, $object, $getValue, $lang));
            }
        } else {
            $structure['options'] = self::getOptions($structure['type'], $layoutDefinition, $object);
        }

        if ($structure['type'] == 'link') {
            $structure['options'] = [
                'allowedTypes' => $layoutDefinition->getAllowedTypes(),
                'allowedTargets' => $layoutDefinition->getAllowedTargets(),
                'disabledFields' => $layoutDefinition->getDisabledFields(),
            ];
        }

        return $structure;
    }

    public static function getObjectRelation($name, $fields)
    {
        $data = [];
        $dataobject = "Pimcore\\Model\\DataObject\\" . $name . '\Listing';

        $listing = new $dataobject();
        foreach ($listing as $key =>  $item) {
            $data[] = [
                'key' => $item->getKey(),
                'value' => $item->getId(),
                'classname' => $item->getClassname(),
            ];
        }
        return $data;
    }

    //type : loại trường đc cấu hình; model : asset, object , document
    public static function getRelationData($type, $model)
    {
        $data = [];
        $listing = '';
        $modelName = '';

        try {
            if ($model == self::KEY_OBJECT) {
                if ($type != 'All') {
                    $modelName = "Pimcore\\Model\\" . $model . "\\" . $type . '\Listing';
                } else {
                    $modelName = "Pimcore\\Model\\" . $model . '\Listing';
                }
            } else {
                $modelName = "Pimcore\\Model\\" . $model . '\Listing';
            }

            $listing = new $modelName();
            if ($listing) {
                // if ($model != 'Asset') {
                //     $listing->setUnpublished(true);
                // }

                if ($model !== self::KEY_OBJECT && $type != 'All') {
                    $listing->setCondition('type = ?', [$type]);
                }

                foreach ($listing as $item) {
                    $key = $model == self::KEY_ASSET ? $item->getFilename() : $item->getKey();
                    if ($key) {
                        $data[] = [
                            'key' => $key,
                            'value' => $item->getId(),
                            'type' => $model,
                            'label' => $key,
                        ];
                    }
                }
            }

            return $data;
        } catch (\Throwable $th) {
            return $data;
        }
    }

    static public function getOptions($type, $layoutDefinition, $object = null)
    {
        $options = [];
        $allowedFieldTypes = ['manyToOneRelation', 'manyToManyRelation', 'advancedManyToManyRelation'];

        if (in_array($type, $allowedFieldTypes)) {
            if ($layoutDefinition->getObjectsAllowed()) {
                $classes = $layoutDefinition->getClasses();
                $blackList = ["user", "role"];
                $listObject = self::getClassList($blackList);

                $options[] = self::getRelationType($classes, self::KEY_OBJECT, 'classes', $listObject);
            }

            if ($layoutDefinition->getDocumentsAllowed()) {
                $document = $layoutDefinition->getDocumentTypes();
                $listDocument = ['email', 'link', 'hardlink', 'snippet', 'folder', 'page'];

                $options[] = self::getRelationType($document, self::KEY_DOCUMENT, 'documentTypes', $listDocument);
            }

            if ($layoutDefinition->getAssetsAllowed()) {
                $asset = $layoutDefinition->getAssetTypes();
                $listAsset = ['archive', 'image', 'audio', 'document', 'text', 'folder', 'video', 'unknown'];

                $options[] = self::getRelationType($asset, self::KEY_ASSET, 'assetTypes', $listAsset);
            }

            // if ($options && count($options) == 1) {
            //     $options = isset($options[0]['children']) ? $options[0]['children'] : [];
            //     if ($options && count($options) == 1) {
            //         $options = $options[0]['children'];
            //     }
            // }
        }

        $allowedObjectTypes = ['manyToManyObjectRelation', 'advancedManyToManyObjectRelation'];
        if (in_array($type, $allowedObjectTypes)) {
            $classes = $layoutDefinition->getClasses();
            $blackList = ["user", "role"];
            $listObject = self::getClassList($blackList);

            $options[] = self::getRelationType($classes, self::KEY_OBJECT, 'classes', $listObject);

            if ($options && count($options) == 1) {
                $options = isset($options[0]['children']) ? $options[0]['children'] : [];
                if ($options && count($options) == 1) {
                    $options = $options[0]['children'];
                }
            }
        }

        $optionTypes = ['gender', 'select', 'multiselect', 'booleanSelect'];
        if (in_array($type, $optionTypes)) {
            // $optionsProviderClass = $layoutDefinition->optionsProviderClass;
            // if ($optionsProviderClass && class_exists($optionsProviderClass) && $object) {
            //     $optionProvider = new $optionsProviderClass;
            //     $options = $optionProvider->getOptions(compact('object'), $layoutDefinition);
            // } else {
                $options = $layoutDefinition->getOptions();
            // }
        }

        if ($type == 'video') {
            if ($layoutDefinition->getAllowedTypes() && count($layoutDefinition->getAllowedTypes())) {
                $options = $layoutDefinition->getAllowedTypes();
            } else $options = $layoutDefinition->getSupportedTypes();
        }

        if ($type == 'fieldcollections') {
            $options = $layoutDefinition->getAllowedTypes();
        }

        return $options;
    }

    // danh sách các classes
    static public function getClassList($blackList)
    {
        $query = 'SELECT * FROM `classes` WHERE id NOT IN ("' . implode('","', $blackList) . '")';
        $classListing = Db::get()->fetchAllAssociative($query);
        $data = [];
        foreach ($classListing as $class) {
            $data[] = $class['name'];
        }

        return $data;
    }

    // danh sách các options theo type
    static public function getRelationType($key, $type, $typeKey, $listKey)
    {
        $options = [
            'label' => $type,
            'value' => $type,
        ];

        if ($key) {
            foreach ($key as $value) {
                $children = self::getRelationData($value[$typeKey], $type);
                if (count($children)) {
                    $datas =  [
                        'label' => $value[$typeKey],
                        'value' => $value[$typeKey],
                        'children' => $children
                    ];

                    $options['children'][] = $datas;
                }
            }
        } else {
            foreach ($listKey as $value) {
                $children = self::getRelationData($value, $type);
                if (count($children)) {
                    $datas =  [
                        'label' => $value,
                        'value' => $value,
                        'children' => $children
                    ];

                    $options['children'][] = $datas;
                }
            }
        }
        return $options;
    }
}
