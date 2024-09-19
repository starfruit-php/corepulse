<?php

namespace CorepulseBundle\Component\Field;

use Pimcore\Model\DataObject\Data\Link as DataLink;

class Link extends Input
{
    public function format($value)
    {
        if ($value) {
            $data = $value->getObjectVars();
            $data['path'] = $value->getPath();

            return $data;
        }

        return null;
    }

    public function formatDataSave($value)
    {
        $link = new DataLink();

        $fields = ['path', 'text', 'title', 'target', 'parameters', 'anchorLink', 'accessKey', 'rel', 'tabIndex', 'class'];
        foreach ($fields as $key) {
            if (isset($value[$key])) {
                $func = 'set' . ucfirst( $key);
                $link->{$func}($value[$key]);
            }
        }

        return $link;
    }
}
