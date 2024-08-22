<?php

namespace CorepulseBundle\Services;

use CorepulseBundle\Services\ClassServices;

class DataObjectServices
{
    // get data listing
    static public function getData($object, $fields, $backlist = false)
    {
        $data = [];
        foreach ($fields as $key => $field) {
            $field = self::convertField($field);

            if ($backlist && in_array($field['fieldtype'], ClassServices::BACKLIST_TYPE) ) {
                continue;
            }

            // if (isset($field['invisible']) && $field['invisible']) {
            //     continue;
            // }

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

    // convert object to array
    static public function convertField($field)
    {
        if (is_object($field)) {
            $result = get_object_vars($field);
            $result['fieldtype'] = $field->getFieldType();

            return $result;
        }

        return $field;
    }

    // save object detail
    static public function saveEdit($object, $updateData)
    {
        foreach ($updateData as $key => $value) {
            $getClass = '\\CorepulseBundle\\Component\\Field\\' . ucfirst($value['type']);
            if (class_exists($getClass)) {
                $component = new $getClass($object, null, $value['value']);
                $data = $component->getDataSave();
                $func = 'set' . ucfirst($key);
                $object->{$func}($data);
            }
        }

        $object->save();

        return $object;
    }
}
