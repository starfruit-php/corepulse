<?php

namespace CorepulseBundle\Services\Helper;

use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;

class SearchHelper
{
    public static function getTree($model, $parentId = '', $key = '', $limit = 0)
    {
        $data = [];
        $conditionQuery = "id is NOT null";
        $conditionParams = [];

        if ($parentId) {
            $conditionQuery = ' AND parentId = :parentId';
            $conditionParams['parentId'] = $parentId;
        }

        if ($key) {
            $key = strtolower($key);

            if ($model != 'asset') {
                $conditionQuery .= " AND (LOWER(`key`) LIKE :key OR LOWER(`path`) LIKE :key)";
            } else {
                $conditionQuery .= " AND (LOWER(`filename`) LIKE :key OR LOWER(`path`) LIKE :key)";
            }

            $conditionParams['key'] = "%" . $key . "%";
        }

        $modelName = '\\Pimcore\\Model\\' . ucfirst($model) . '\Listing';

        $listing = new $modelName();
        $listing->setCondition($conditionQuery, $conditionParams);
        $listing->setOrderKey('id');
        $listing->setOrder('ASC');

        if ($limit) {
            $listing->setLimit($limit);
        }

        if ($model != 'asset') {
            $listing->setUnpublished(true);
        }

        $listing->load();

        foreach ($listing as $item) {
            $class = '';

            if ($item->getType()) {
                if ($model == 'dataObject' && $item->getType() != 'folder') {
                    $class = $item->getClassName();
                }

                $icon = self::getIcon($item->getType());
                $route = self::getRoute($model);

                $data[] = [
                    'id' => $item->getId(),
                    'name' => $item->getId() == 1 ? 'home' : $item->getKey(),
                    'path' => $item->getFullPath(),
                    'type' => $item->getType(),
                    'model' => $model,
                    'class' => $class,
                    'icon' => $icon,
                    'route' => $route,
                ];
            }
        }

        return $data;
    }

    public static function getRoute($type)
    {
        $route = '';
        if ($type == 'dataObject') {
            $route = 'vuetify_object_detail';
        }

        if ($type == 'asset') {
            $route = 'vuetify_asset_detail';
        }

        if ($type == 'document') {
            $route = 'vuetify_doc_detail';
        }

        return $route;
    }

    public static function getIcon($type)
    {
        $key = [
            "folder" => 'mdi-folder-outline',
            "object" => 'mdi-cube-outline',
            "image" => 'mdi-image',
            "document" => 'mdi-file-document',
            "text" => 'mdi-text',
            "video" => 'mdi-video',
            "unknown" => 'mdi-crosshairs-question',
            "page" => 'mdi-book-open-page-variant',
            "link" => 'mdi-link',
            "snippet" => 'mdi-file-code',
            "email" => 'mdi-email',
            "hardlink" => 'mdi-vector-link',
            'class' => 'mdi-book-multiple',
            'create' => 'mdi-plus',
            'listing' => 'mdi-menu',
            'printcontainer' => 'mdi-book-open-blank-variant',
            'printpage' => 'mdi-printer',
        ];

        if (array_key_exists($type, $key)) {
            return $key[$type];
        }

        return 'mdi-help-circle-outline';
    }

    public static function getClassSearch($action)
    {
        $datas = [];
        $classSetting = Db::get()->fetchAssociative('SELECT `config` FROM `vuetify_settings` WHERE `type` = "object"', []);
        if ($classSetting) {
            $classSetting = json_decode($classSetting['config'], true);
        }

        if ($classSetting && count($classSetting)) {
            foreach ($classSetting as $class) {
                $classDefinition = ClassDefinition::getById($class);
                if ($classDefinition) {
                    $data = [
                        "id" => $classDefinition->getId(),
                        "name" => $classDefinition->getName(),
                        "value" => $classDefinition->getId(),
                        "key" => $classDefinition->getName(),
                        "title" => $action,
                        "type" => "class",
                        "model" => "class",
                        "class" => "class",
                        "icon" => self::getIcon($action),
                        "action" => $action,
                    ];

                    $datas[] = $data;
                }
            }
        }

        return $datas;
    }
}
