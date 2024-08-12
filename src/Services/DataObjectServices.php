<?php

namespace CorepulseBundle\Services;

class DataObjectServices
{
    static public function getData($object, $fields)
    {
        $data = [];
        foreach ($fields as $key => $field) {
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
}
