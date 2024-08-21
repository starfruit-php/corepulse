<?php

namespace CorepulseBundle\Component\Field;

use Starfruit\BuilderBundle\Tool\AssetTool;
use Pimcore\Model\Asset;

class Image extends Input
{
    public function format($value)
    {
        $value = AssetTool::getPath($value, true);

        return $value;
    }

    public function formatBlock($value)
    {
        $value = AssetTool::getPath($value, true);

        return $value;
    }

    public function formatDataSave($value)
    {
        $image = Asset::getById((int)$value);

        return $image;
    }
}
