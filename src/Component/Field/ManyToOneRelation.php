<?php

namespace CorepulseBundle\Component\Field;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use CorepulseBundle\Services\ClassServices;
use Pimcore\Db;

class ManyToOneRelation extends Select
{
    public function format($value)
    {
        if ($value) {
            return $this->getElementType($value);
        }

        return null;
    }

    public function formatDataSave($value)
    {
        $data = null;
        if ($value && isset($value["type"])) {
            switch ($value["type"]) {
                case 'asset':
                    $data = Asset::getById($value['id']);
                    break;
                case 'document':
                    $data = Document::getById($value['id']);
                    break;
                case 'object':
                    $data = DataObject::getById($value['id']);
                    break;

                default:
                    $data = DataObject::getById($value['id']);
                    break;
            }
        }

        return $data;
    }

    public function getElementType(ElementInterface $element)
    {
        $data = null;

        $visibleFields = ['key', 'path', 'fullpath'];
        $displayMode = $this->layout->displayMode;

        if (property_exists($this->layout, 'visibleFields') && $this->layout->visibleFields) {
            $visibleFields = explode(',', $this->layout->visibleFields);
        }

        if ($element instanceof Asset) {
            $data = [
                'type' => 'asset',
                'id' => $element->getId(),
                'subType' => $element->getType()
            ];
        }

        if ($element instanceof Document) {
            $data = [
                'type' => 'document',
                'id' => $element->getId(),
                'subType' => $element->getType()
            ];
            if ($key = array_search("filename", $visibleFields)) {
                unset($visibleFields[$key]);
            }
        }

        if ($element instanceof DataObject\AbstractObject) {
            $data = [
                'type' => 'object',
                'id' => $element->getId(),
                'subType' => $element->getClassName()
            ];

            if ($key = array_search("filename", $visibleFields)) {
                unset($visibleFields[$key]);
            }
        }

        foreach ($visibleFields as $field) {
            $value = $element->{'get' . ucfirst($field)}();
            if (in_array($field, self::SYSTEM_CONVERT_DATE)) {
                $value = date('Y/m/d', $value);
            }

            $data[$field] = $value;
        }

        return $data;
    }

    public function getOption()
    {
        $layoutDefinition = $this->layout;

        $data = [];

        if ($layoutDefinition->objectsAllowed) {
            $classes = $layoutDefinition->classes;
            $blackList = ["user", "role"];
            $listObject = self::getClassList($blackList);

            $data[] = self::getRelationType($classes, ClassServices::KEY_OBJECT, 'classes', $listObject);
        }

        if ($layoutDefinition->documentsAllowed) {
            $document = $layoutDefinition->documentTypes;
            $listDocument = ['email', 'link', 'hardlink', 'snippet', 'folder', 'page'];

            $data[] = self::getRelationType($document, ClassServices::KEY_DOCUMENT, 'documentTypes', $listDocument);
        }

        if ($layoutDefinition->assetsAllowed) {
            $asset = $layoutDefinition->assetTypes;
            $listAsset = ['archive', 'image', 'audio', 'document', 'text', 'folder', 'video', 'unknown'];

            $data[] = self::getRelationType($asset, ClassServices::KEY_ASSET, 'assetTypes', $listAsset);
        }

        // if ($options && count($options) == 1) {
        //     $options = isset($options[0]['children']) ? $options[0]['children'] : [];
        //     if ($options && count($options) == 1) {
        //         $options = $options[0]['children'];
        //     }
        // }

        return $data;
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
            if ($model == ClassServices::KEY_OBJECT) {
                if ($type != 'All' && $type != 'folder') {
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

                if ($model !== ClassServices::KEY_OBJECT && $type != 'All' || ($model == ClassServices::KEY_OBJECT && $type == 'folder')) {
                    $listing->setCondition('type = ?', [$type]);
                }

                foreach ($listing as $item) {
                    $key = ($model == ClassServices::KEY_ASSET) ? $item->getFilename() : $item->getKey();

                    $data[] = [
                        'key' => $key,
                        'value' => $item->getId(),
                        'type' => $model,
                        'label' => $item->getFullPath(),
                    ];
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

                $options[] = self::getRelationType($classes, ClassServices::KEY_OBJECT, 'classes', $listObject);
            }

            if ($layoutDefinition->getDocumentsAllowed()) {
                $document = $layoutDefinition->getDocumentTypes();
                $listDocument = ['email', 'link', 'hardlink', 'snippet', 'folder', 'page'];

                $options[] = self::getRelationType($document, ClassServices::KEY_DOCUMENT, 'documentTypes', $listDocument);
            }

            if ($layoutDefinition->getAssetsAllowed()) {
                $asset = $layoutDefinition->getAssetTypes();
                $listAsset = ['archive', 'image', 'audio', 'document', 'text', 'folder', 'video', 'unknown'];

                $options[] = self::getRelationType($asset, ClassServices::KEY_ASSET, 'assetTypes', $listAsset);
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

            $options[] = self::getRelationType($classes, ClassServices::KEY_OBJECT, 'classes', $listObject);

            // if ($options && count($options) == 1) {
            //     $options = isset($options[0]['children']) ? $options[0]['children'] : [];
            //     if ($options && count($options) == 1) {
            //         $options = $options[0]['children'];
            //     }
            // }
        }

        // $optionTypes = ['gender', 'select', 'multiselect', 'booleanSelect'];
        // if (in_array($type, $optionTypes)) {
        //     $optionsProviderClass = $layoutDefinition->optionsProviderClass;
        //     if ($optionsProviderClass && class_exists($optionsProviderClass) && $object) {
        //         $optionProvider = new $optionsProviderClass;
        //         $options = $optionProvider->getOptions(compact('object'), $layoutDefinition);
        //     } else {
        //         $options = $layoutDefinition->getOptions();
        //     }
        // }

        // if ($type == 'video') {
        //     if ($layoutDefinition->getAllowedTypes() && count($layoutDefinition->getAllowedTypes())) {
        //         $options = $layoutDefinition->getAllowedTypes();
        //     } else $options = $layoutDefinition->getSupportedTypes();
        // }

        // if ($type == 'fieldcollections') {
        //     $options = $layoutDefinition->getAllowedTypes();
        // }

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

    public function getFrontEndType(): string
    {
        return 'relation';
    }
}
