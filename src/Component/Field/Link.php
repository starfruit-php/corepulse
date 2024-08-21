<?php

namespace CorepulseBundle\Component\Field;

use Pimcore\Model\DataObject\Data\Link as DataLink;

class Link extends Input
{
    public function format($value)
    {
        if ($value) {
            return $value->getObjectVars();
        }

        return null;
    }

    public function formatDataSave($value)
    {
        $link = new DataLink();

        foreach ($value as $k => $v) {
            $func = 'set' . ucfirst($k);
            if ($v) {
                $link->{$func}($v);
            }
        }

        return $link;
    }
}
