<?php

namespace CorepulseBundle\Component\Field;

use Starfruit\BuilderBundle\Tool\AssetTool;

class Image extends Input
{
    public function format($value)
    {
        $value = AssetTool::getPath($value, true);

        return $value;
    }
}
