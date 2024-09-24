<?php

namespace CorepulseBundle\Services\Helper;

class ObjectHelper
{
    static public function getMethodData($object, $method)
    {
        $func = "get" . ucfirst(($method));

        return property_exists($object, $method) ? $object->$func() : null;
    }
}
