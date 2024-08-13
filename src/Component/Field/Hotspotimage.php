<?php

namespace CorepulseBundle\Component\Field;

use Starfruit\BuilderBundle\Tool\AssetTool;

class Hotspotimage extends Image
{
    public function format($value)
    {
        if ($value) {
            $result = [];
            $result['hotspots'] = $value->getHotspots();
            $result['marker'] = $value->getMarker();
            $result['crop'] = $value->getCrop();
            $result['image'] = AssetTool::getPath($value->getImage(), true);

            return $result;
        }

        return null;
    }
}
