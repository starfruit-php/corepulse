<?php

namespace CorepulseBundle\Component\Field;

use Starfruit\BuilderBundle\Tool\AssetTool;

class Video extends Image
{
    public function format($value)
    {
        return AssetTool::getVideo($value);
    }

    public function getFrontEndType():string
    {
        return 'video';
    }
}
