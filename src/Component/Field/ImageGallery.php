<?php

namespace CorepulseBundle\Component\Field;

use Starfruit\BuilderBundle\Tool\AssetTool;

class ImageGallery extends Image
{
    public function format($value)
    {
        $value = AssetTool::getPaths($value, true);

        return $value;
    }
}
