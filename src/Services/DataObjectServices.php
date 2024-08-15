<?php

namespace CorepulseBundle\Services;

use CorepulseBundle\Services\ClassServices;

class DataObjectServices
{
    static public function getData($object, $fields)
    {
        $data = [];
        foreach ($fields as $key => $field) {
            $field = self::convertField($field);

            if (isset($field['invisible']) && $field['invisible']) {
                continue;
            }

            $getClass = '\\CorepulseBundle\\Component\\Field\\' . ucfirst($field['fieldtype']);
            if (!class_exists($getClass)) {
                $data[$key] = null;
                continue;
            }

            $value = new $getClass($object, $field);

            $data[$key] = $value->getValue();
        }

        return $data;
    }

    static public function getSidebarData($object)
    {
        $fields = ClassServices::systemField();

        $data = self::getData($object, $fields);

        return $data;
    }

    static public function convertField($field)
    {
        if (is_object($field)) {
            $result = get_object_vars($field);
            $result['fieldtype'] = $field->getFieldType();

            return $result;
        }

        return $field;
    }
}
