<?php

namespace CorepulseBundle\Component\Field;

use Starfruit\BuilderBundle\Tool\AssetTool;
use Pimcore\Model\Asset;

class Image extends Input
{
    public function format($value)
    {
        $data = AssetTool::getPath($value, true);

        if ($value) {
            $data['id'] = $value->getId();
        }

        return $data;
    }

    public function formatBlock($value)
    {
        return  $this->format($value);
    }

    public function formatDataSave($value)
    {
        $image = Asset::getById((int)$value);

        return $image;
    }

    public function getFrontEndType():string
    {
        return 'image';
    }
}
