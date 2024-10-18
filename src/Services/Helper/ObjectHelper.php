<?php

namespace CorepulseBundle\Services\Helper;

class ObjectHelper
{
    static public function getMethodData($object, $method)
    {
        $func = "get" . ucfirst(($method));

        return property_exists($object, $method) ? ($method === 'published' ? self::getPublished($object) : $object->$func()) : null;
    }
    
    static public function getPublished($object)
    {
        $draft = self::checkLastest($object);
        $status = 'Draft';
        if (!$draft) {
            if ($object->getPublished()) {
                $status = 'Publish';
            } else {
                $status = 'Unpublish';
            }
        }

        return $status;
    }

    static public function checkLastest($object)
    {
        $lastest = self::getLastest($object);

        if ($lastest) {
            return $object->getModificationDate() !== $lastest->getModificationDate();
        }
        return false;
    }

    static public function getLastest($object)
    {
        $versions = $object->getVersions();

        if (empty($versions)) {
            return $object;
        }

        $previousVersion = $versions[count($versions) - 1];
        $previousObject = $previousVersion->getData();
        return $previousObject;
    }
}
